<?php

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Moderation\Contracts\ModerationActionRepository;
use App\Domain\Moderation\Enums\SubmissionItemType;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Models\Chapter;
use App\Models\InitialSubmission;
use App\Models\ModerationAction;
use App\Models\Novel;
use App\Models\SubmissionItem;
use App\Models\User;

function auditUser(?UserRole $role = null): User
{
    return User::factory()->create([
        'role' => ($role ?? UserRole::Member)->value,
    ]);
}

function auditFixture(): array
{
    $author = auditUser();
    $novel = Novel::factory()->create([
        'user_id' => $author->id,
        'status' => NovelStatus::InitialReview->value,
    ]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);
    $submission = InitialSubmission::factory()->create([
        'novel_id' => $novel->id,
        'submitted_by' => $author->id,
        'status' => SubmissionStatus::Submitted->value,
    ]);
    SubmissionItem::factory()->create([
        'submission_id' => $submission->id,
        'item_type' => SubmissionItemType::Chapter->value,
        'chapter_id' => $chapter->id,
    ]);

    return compact('author', 'novel', 'chapter', 'submission');
}

it('records exactly one audit action for each moderation decision', function (
    string $decision,
    ?string $reason,
    NovelStatus $expectedNovelStatus,
    ChapterStatus $expectedChapterStatus,
) {
    $fixture = auditFixture();
    $moderator = auditUser(UserRole::Moderator);

    $this->actingAs($moderator)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => $decision,
            'notes' => $reason,
        ])
        ->assertRedirect(route('moderation.queue'));

    $action = ModerationAction::query()->sole();

    expect(ModerationAction::query()->count())->toBe(1)
        ->and($action->moderator_id)->toBe($moderator->id)
        ->and($action->target_type)->toBe((new InitialSubmission)->getMorphClass())
        ->and($action->target_id)->toBe($fixture['submission']->id)
        ->and($action->action)->toBe($decision)
        ->and($action->reason)->toBe($reason)
        ->and($action->metadata)->toBe([
            'novel_id' => $fixture['novel']->id,
            'submission_id' => $fixture['submission']->id,
            'decision' => $decision,
        ])
        ->and($fixture['novel']->fresh()->status)->toBe($expectedNovelStatus)
        ->and($fixture['chapter']->fresh()->status)->toBe($expectedChapterStatus);
})->with([
    'approved' => ['approved', null, NovelStatus::Published, ChapterStatus::Published],
    'revision required' => ['revision_required', 'Perbaiki sinopsis.', NovelStatus::RevisionRequired, ChapterStatus::Draft],
    'rejected' => ['rejected', 'Konten melanggar pedoman.', NovelStatus::Rejected, ChapterStatus::Draft],
]);

it('rolls back the decision when audit recording fails', function () {
    $fixture = auditFixture();
    $moderator = auditUser(UserRole::Moderator);
    $repository = Mockery::mock(ModerationActionRepository::class);
    $repository->shouldReceive('recordInitialSubmissionDecision')
        ->once()
        ->andThrow(new RuntimeException('audit failed'));
    $this->app->instance(ModerationActionRepository::class, $repository);
    $this->withoutExceptionHandling();

    try {
        $this->actingAs($moderator)
            ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
                'decision' => 'approved',
            ]);
        $this->fail('Expected audit failure.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('audit failed');
    }

    expect($fixture['submission']->fresh()->status)->toBe(SubmissionStatus::Submitted)
        ->and($fixture['novel']->fresh()->status)->toBe(NovelStatus::InitialReview)
        ->and($fixture['chapter']->fresh()->status)->toBe(ChapterStatus::Draft)
        ->and(ModerationAction::query()->count())->toBe(0);
});
