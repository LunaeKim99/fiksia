<?php

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Models\Chapter;
use App\Models\InitialSubmission;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

function rateLimitUser(?UserRole $role = null): User
{
    return User::factory()->create([
        'role' => ($role ?? UserRole::Member)->value,
    ]);
}

function submitReviewFixture(User $user): array
{
    $novel = Novel::factory()->create([
        'user_id' => $user->id,
        'status' => NovelStatus::Draft->value,
    ]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);

    return compact('novel', 'chapter');
}

function moderationRateLimitFixture(User $author): InitialSubmission
{
    $novel = Novel::factory()->create([
        'user_id' => $author->id,
        'status' => NovelStatus::InitialReview->value,
    ]);

    return InitialSubmission::factory()->create([
        'novel_id' => $novel->id,
        'submitted_by' => $author->id,
        'status' => SubmissionStatus::Submitted->value,
    ]);
}

beforeEach(function (): void {
    Cache::flush();
});

it('allows exactly ten submit-review requests per user and isolates users', function () {
    $user = rateLimitUser();
    $lastAllowed = null;

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $fixture = submitReviewFixture($user);
        $lastAllowed = $this->actingAs($user)->post(route('studio.novels.submitReview', [
            'novel' => $fixture['novel']->id,
        ]), [
            'chapter_ids' => [$fixture['chapter']->id],
        ]);
    }

    expect($lastAllowed)->not->toBeNull()
        ->and($lastAllowed->status())->toBe(302)
        ->and($lastAllowed->headers->get('X-RateLimit-Remaining'))->toBe('0');

    $blockedFixture = submitReviewFixture($user);
    $blocked = $this->actingAs($user)->post(route('studio.novels.submitReview', [
        'novel' => $blockedFixture['novel']->id,
    ]), [
        'chapter_ids' => [$blockedFixture['chapter']->id],
    ]);

    expect($blocked->status())->toBe(429);

    $otherUser = rateLimitUser();
    $otherFixture = submitReviewFixture($otherUser);
    $otherResponse = $this->actingAs($otherUser)->post(route('studio.novels.submitReview', [
        'novel' => $otherFixture['novel']->id,
    ]), [
        'chapter_ids' => [$otherFixture['chapter']->id],
    ]);

    expect($otherResponse->status())->toBe(302);
});

it('allows exactly thirty moderation decisions per user and isolates users', function () {
    $author = rateLimitUser();
    $moderator = rateLimitUser(UserRole::Moderator);
    $lastAllowed = null;

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $submission = moderationRateLimitFixture($author);
        $lastAllowed = $this->actingAs($moderator)->post(route('moderation.submissions.decide', [
            'submission' => $submission->id,
        ]), [
            'decision' => 'approved',
        ]);
    }

    expect($lastAllowed)->not->toBeNull()
        ->and($lastAllowed->status())->toBe(302)
        ->and($lastAllowed->headers->get('X-RateLimit-Remaining'))->toBe('0');

    $blockedSubmission = moderationRateLimitFixture($author);
    $blocked = $this->actingAs($moderator)->post(route('moderation.submissions.decide', [
        'submission' => $blockedSubmission->id,
    ]), [
        'decision' => 'approved',
    ]);

    expect($blocked->status())->toBe(429);

    $otherModerator = rateLimitUser(UserRole::Moderator);
    $otherSubmission = moderationRateLimitFixture($author);
    $otherResponse = $this->actingAs($otherModerator)->post(route('moderation.submissions.decide', [
        'submission' => $otherSubmission->id,
    ]), [
        'decision' => 'approved',
    ]);

    expect($otherResponse->status())->toBe(302);
});

it('allows exactly sixty reader-progress requests per user and isolates users', function () {
    $user = rateLimitUser();
    $novel = Novel::factory()->published()->create();
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
    ]);
    $lastAllowed = null;

    for ($attempt = 0; $attempt < 60; $attempt++) {
        $lastAllowed = $this->actingAs($user)->post(route('reader.progress'), [
            'novel_id' => $novel->id,
            'chapter_id' => $chapter->id,
        ]);
    }

    expect($lastAllowed)->not->toBeNull()
        ->and($lastAllowed->status())->toBe(204)
        ->and($lastAllowed->headers->get('X-RateLimit-Remaining'))->toBe('0');

    $blocked = $this->actingAs($user)->post(route('reader.progress'), [
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
    ]);

    expect($blocked->status())->toBe(429);

    $otherUser = rateLimitUser();
    $otherResponse = $this->actingAs($otherUser)->post(route('reader.progress'), [
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
    ]);

    expect($otherResponse->status())->toBe(204);
});
