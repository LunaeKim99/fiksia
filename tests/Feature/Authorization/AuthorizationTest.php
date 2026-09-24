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
use App\Policies\ChapterPolicy;
use App\Policies\InitialSubmissionPolicy;
use App\Policies\NovelPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

function authzUser(?UserRole $role = null): User
{
    return User::factory()->create([
        'role' => ($role ?? UserRole::Member)->value,
    ]);
}

function authzNovel(User $owner, NovelStatus $status = NovelStatus::Draft): Novel
{
    return Novel::factory()->create([
        'user_id' => $owner->id,
        'status' => $status->value,
    ]);
}

function authzSubmission(User $author): array
{
    $novel = authzNovel($author, NovelStatus::InitialReview);
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

    return compact('novel', 'chapter', 'submission');
}

it('auto-discovers model policies', function () {
    expect(Gate::getPolicyFor(Novel::class))->toBeInstanceOf(NovelPolicy::class)
        ->and(Gate::getPolicyFor(Chapter::class))->toBeInstanceOf(ChapterPolicy::class)
        ->and(Gate::getPolicyFor(InitialSubmission::class))->toBeInstanceOf(InitialSubmissionPolicy::class);
});

it('allows guests to read published novels and chapters', function () {
    $author = authzUser();
    $novel = authzNovel($author, NovelStatus::Published);
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 1,
    ]);

    $this->get(route('novels.show', ['novel' => $novel->slug]))->assertOk();
    $this->get(route('novels.chapters.show', [
        'novel' => $novel->slug,
        'chapter' => $chapter->chapter_number,
    ]))->assertOk();
});

it('returns 404 for guests on draft public novel and chapter routes', function () {
    $author = authzUser();
    $draftNovel = authzNovel($author);
    $draftChapter = Chapter::factory()->create([
        'novel_id' => $draftNovel->id,
        'chapter_number' => 1,
    ]);
    $publishedNovel = authzNovel($author, NovelStatus::Published);
    $publishedNovelDraftChapter = Chapter::factory()->create([
        'novel_id' => $publishedNovel->id,
        'chapter_number' => 1,
    ]);

    $this->get(route('novels.show', ['novel' => $draftNovel->slug]))->assertNotFound();
    $this->get(route('novels.chapters.show', [
        'novel' => $draftNovel->slug,
        'chapter' => $draftChapter->chapter_number,
    ]))->assertNotFound();
    $this->get(route('novels.chapters.show', [
        'novel' => $publishedNovel->slug,
        'chapter' => $publishedNovelDraftChapter->chapter_number,
    ]))->assertNotFound();
});

it('returns 404 on public novel route even for its owner', function () {
    $owner = authzUser();
    $novel = authzNovel($owner);

    $this->actingAs($owner)
        ->get(route('novels.show', ['novel' => $novel->slug]))
        ->assertNotFound();
});

it('returns 404 on public chapter route even for its owner', function () {
    $owner = authzUser();
    $novel = authzNovel($owner, NovelStatus::Published);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 1,
        'status' => ChapterStatus::Draft->value,
    ]);

    $this->actingAs($owner)
        ->get(route('novels.chapters.show', [
            'novel' => $novel->slug,
            'chapter' => $chapter->chapter_number,
        ]))
        ->assertNotFound();
});

it('keeps unpublished covers forbidden before checking file existence', function () {
    Storage::fake('local');
    $author = authzUser();
    $novel = authzNovel($author);
    $novel->update(['cover_path' => 'covers/missing.jpg']);

    $this->get(route('novels.cover', ['novel' => $novel->id]))->assertForbidden();

    $novel->update(['cover_path' => null]);
    $this->get(route('novels.cover', ['novel' => $novel->id]))->assertForbidden();
});

it('allows owner and moderator to view unpublished covers', function () {
    Storage::fake('local');
    Storage::disk('local')->put('covers/cover.jpg', 'image');
    $author = authzUser();
    $moderator = authzUser(UserRole::Moderator);
    $novel = authzNovel($author);
    $novel->update(['cover_path' => 'covers/cover.jpg']);

    $this->actingAs($author)->get(route('novels.cover', ['novel' => $novel->id]))->assertOk();
    $this->actingAs($moderator)->get(route('novels.cover', ['novel' => $novel->id]))->assertOk();
});

it('forbids members from opening another users studio novel', function () {
    $owner = authzUser();
    $member = authzUser();
    $novel = authzNovel($owner);

    $this->actingAs($member)
        ->get(route('studio.novels.show', ['novel' => $novel->id]))
        ->assertForbidden();
});

it('allows owner to open own studio novel and update own draft novel policy', function () {
    $owner = authzUser();
    $novel = authzNovel($owner);

    $this->actingAs($owner)
        ->get(route('studio.novels.show', ['novel' => $novel->id]))
        ->assertOk();

    expect(Gate::forUser($owner)->allows('update', $novel))->toBeTrue();
});

it('forbids a member from editing another users draft chapter', function () {
    $owner = authzUser();
    $member = authzUser();
    $novel = authzNovel($owner, NovelStatus::Published);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);

    $this->actingAs($member)
        ->post(route('studio.chapters.update', ['chapter' => $chapter->id]), [
            'title' => 'Intrusion',
            'content' => 'Intrusion.',
        ])
        ->assertForbidden();
});

it('allows owner to update own draft chapter', function () {
    $owner = authzUser();
    $novel = authzNovel($owner, NovelStatus::Published);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);

    $this->actingAs($owner)
        ->post(route('studio.chapters.update', ['chapter' => $chapter->id]), [
            'title' => 'Bab Diperbarui',
            'content' => 'Isi baru.',
        ])
        ->assertRedirect();

    expect($chapter->fresh()->title)->toBe('Bab Diperbarui');
});

it('allows owner to publish own draft chapter on a published novel', function () {
    $owner = authzUser();
    $novel = authzNovel($owner, NovelStatus::Published);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);

    $this->actingAs($owner)
        ->post(route('studio.chapters.publish', ['chapter' => $chapter->id]))
        ->assertRedirect();

    expect($chapter->fresh()->status)->toBe(ChapterStatus::Published);
});

it('forbids non-owner from publishing a chapter on a published novel', function () {
    $owner = authzUser();
    $member = authzUser();
    $novel = authzNovel($owner, NovelStatus::Published);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);

    $this->actingAs($member)
        ->post(route('studio.chapters.publish', ['chapter' => $chapter->id]))
        ->assertForbidden();
});

it('forbids members from moderation queue, submission, and decision', function () {
    $fixture = authzSubmission(authzUser());
    $member = authzUser();

    $this->actingAs($member)->get(route('moderation.queue'))->assertForbidden();
    $this->actingAs($member)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertForbidden();
    $this->actingAs($member)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'approved',
        ])
        ->assertForbidden();
});

it('returns 403 before validation for a member invalid moderation decision', function () {
    $fixture = authzSubmission(authzUser());
    $member = authzUser();

    $this->actingAs($member)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'invalid',
        ])
        ->assertForbidden();
});

it('returns 403 before validation when a submitter sends an invalid decision', function () {
    $author = authzUser(UserRole::Moderator);
    $fixture = authzSubmission($author);

    $this->actingAs($author)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'invalid',
        ])
        ->assertForbidden();
});

it('returns 403 before validation when a non-owner creates a chapter with an invalid title', function () {
    $owner = authzUser();
    $intruder = authzUser();
    $novel = authzNovel($owner);

    $this->actingAs($intruder)
        ->post(route('studio.chapters.store', ['novel' => $novel->id]), [
            'title' => '',
            'content' => 'Isi.',
        ])
        ->assertForbidden();
});

it('returns 403 before validation when a non-owner updates a chapter with an invalid title', function () {
    $owner = authzUser();
    $intruder = authzUser();
    $novel = authzNovel($owner, NovelStatus::Published);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'status' => ChapterStatus::Draft->value,
    ]);

    $this->actingAs($intruder)
        ->post(route('studio.chapters.update', ['chapter' => $chapter->id]), [
            'title' => '',
            'content' => 'Isi.',
        ])
        ->assertForbidden();
});

it('returns 403 before validation when a non-owner submits review with an invalid payload', function () {
    $owner = authzUser();
    $intruder = authzUser();
    $novel = authzNovel($owner);

    $this->actingAs($intruder)
        ->post(route('studio.novels.submitReview', ['novel' => $novel->id]), [
            'chapter_ids' => [],
        ])
        ->assertForbidden();
});

it('forbids an author from reviewing their own submission', function () {
    $author = authzUser(UserRole::Moderator);
    $fixture = authzSubmission($author);

    $this->actingAs($author)
        ->post(route('moderation.submissions.decide', ['submission' => $fixture['submission']->id]), [
            'decision' => 'approved',
        ])
        ->assertForbidden();
});

it('allows moderators and novel owners to view submissions', function () {
    $author = authzUser();
    $moderator = authzUser(UserRole::Moderator);
    $fixture = authzSubmission($author);

    $this->actingAs($moderator)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertOk();

    $this->actingAs($author)
        ->get(route('moderation.submissions.show', ['submission' => $fixture['submission']->id]))
        ->assertOk();
});
