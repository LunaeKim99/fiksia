<?php

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Moderation\Enums\SubmissionItemType;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Models\Chapter;
use App\Models\InitialSubmission;
use App\Models\Novel;
use App\Models\SubmissionItem;
use App\Models\User;

function moderationUser(?UserRole $role = null): User
{
    return User::factory()->create([
        'role' => ($role ?? UserRole::Member)->value,
    ]);
}

function moderationFixture(): array
{
    $author = moderationUser();
    $novel = Novel::factory()->create([
        'user_id' => $author->id,
        'title' => 'Novel Moderasi',
        'synopsis' => 'Sinopsis untuk review.',
        'status' => NovelStatus::InitialReview->value,
    ]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 1,
        'title' => 'Bab Moderasi',
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
        'snapshot' => [
            'chapter_number' => $chapter->chapter_number,
            'title' => $chapter->title,
        ],
        'sort_order' => 0,
    ]);

    return compact('author', 'novel', 'chapter', 'submission');
}

it('forbids members from moderation queue', function () {
    $member = moderationUser();

    $this->actingAs($member)
        ->get('/moderation/queue')
        ->assertForbidden();
});

it('forbids members from viewing a submission', function () {
    $fixture = moderationFixture();
    $member = moderationUser();

    $this->actingAs($member)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertForbidden();
});

it('returns 422 for invalid moderator decision', function () {
    $fixture = moderationFixture();
    $moderator = moderationUser(UserRole::Moderator);

    $this->actingAs($moderator)
        ->from(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'invalid',
        ])
        ->assertStatus(422);
});

it('returns 403 for member invalid decision', function () {
    $fixture = moderationFixture();
    $member = moderationUser();

    $this->actingAs($member)
        ->from(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'invalid',
        ])
        ->assertForbidden();
});

it('shows submitted and reviewing items to moderators', function () {
    $fixture = moderationFixture();
    $moderator = moderationUser(UserRole::Moderator);

    $this->actingAs($moderator)
        ->get('/moderation/queue')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Moderation/Queue')
            ->where('submissions.data.0.id', $fixture['submission']->id)
            ->where('submissions.data.0.novel.title', 'Novel Moderasi')
            ->where('submissions.data.0.submittedBy.name', $fixture['author']->name)
        );
});

it('shows review workspace and decided state read only', function () {
    $fixture = moderationFixture();
    $moderator = moderationUser(UserRole::Moderator);

    $this->actingAs($moderator)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Moderation/Submission')
            ->where('submission.id', $fixture['submission']->id)
            ->where('submission.novel.title', 'Novel Moderasi')
            ->where('submission.items.0.chapterNumber', 1)
            ->where('submission.items.0.title', 'Bab Moderasi')
        );

    $fixture['submission']->update([
        'status' => SubmissionStatus::Approved->value,
        'review_notes' => 'Catatan review.',
        'decided_at' => now(),
    ]);

    $this->actingAs($moderator)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertInertia(fn ($page) => $page
            ->where('submission.status', 'approved')
            ->where('submission.isDecided', true)
        );
});

it('approves a submission and publishes its chapters', function () {
    $fixture = moderationFixture();
    $moderator = moderationUser(UserRole::Moderator);

    $this->actingAs($moderator)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'approved',
        ])
        ->assertRedirect(route('moderation.queue'))
        ->assertSessionHas('success', 'Keputusan review tersimpan.');

    expect($fixture['novel']->fresh()->status)->toBe(NovelStatus::Published)
        ->and($fixture['chapter']->fresh()->status)->toBe(ChapterStatus::Published)
        ->and($fixture['submission']->fresh()->status)->toBe(SubmissionStatus::Approved);
});

it('rejects revision decision without notes', function () {
    $fixture = moderationFixture();
    $moderator = moderationUser(UserRole::Moderator);

    $this->actingAs($moderator)
        ->from(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'revision_required',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Catatan review wajib diisi untuk revisi atau penolakan.');

    expect($fixture['submission']->fresh()->status)->toBe(SubmissionStatus::Submitted)
        ->and($fixture['novel']->fresh()->status)->toBe(NovelStatus::InitialReview);
});

it('prevents moderators from moderating their own submission', function () {
    $moderator = moderationUser(UserRole::Moderator);
    $novel = Novel::factory()->create([
        'user_id' => $moderator->id,
        'status' => NovelStatus::InitialReview->value,
    ]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);
    $submission = InitialSubmission::factory()->create([
        'novel_id' => $novel->id,
        'submitted_by' => $moderator->id,
    ]);
    SubmissionItem::factory()->create([
        'submission_id' => $submission->id,
        'item_type' => SubmissionItemType::Chapter->value,
        'chapter_id' => $chapter->id,
    ]);

    $this->actingAs($moderator)
        ->from(route('moderation.submissions.show', ['submission' => $submission->id]))
        ->post(route('moderation.submissions.decide', ['submission' => $submission->id]), [
            'decision' => 'approved',
        ])
        ->assertForbidden();

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Submitted);
});

it('forbids members from deciding submissions', function () {
    $fixture = moderationFixture();
    $member = moderationUser();

    $this->actingAs($member)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'approved',
        ])
        ->assertForbidden();
});
