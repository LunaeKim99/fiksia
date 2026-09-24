<?php

use App\Application\Reader\Commands\AddBookmark\AddBookmarkCommand;
use App\Application\Reader\Commands\AddBookmark\AddBookmarkHandler;
use App\Application\Reader\Commands\FollowNovel\FollowNovelCommand;
use App\Application\Reader\Commands\FollowNovel\FollowNovelHandler;
use App\Application\Reader\Commands\RemoveBookmark\RemoveBookmarkCommand;
use App\Application\Reader\Commands\RemoveBookmark\RemoveBookmarkHandler;
use App\Application\Reader\Commands\SaveNovel\SaveNovelCommand;
use App\Application\Reader\Commands\SaveNovel\SaveNovelHandler;
use App\Application\Reader\Commands\SaveReadingProgress\SaveReadingProgressCommand;
use App\Application\Reader\Commands\SaveReadingProgress\SaveReadingProgressHandler;
use App\Application\Reader\Commands\UnfollowNovel\UnfollowNovelCommand;
use App\Application\Reader\Commands\UnfollowNovel\UnfollowNovelHandler;
use App\Application\Reader\Commands\UnsaveNovel\UnsaveNovelCommand;
use App\Application\Reader\Commands\UnsaveNovel\UnsaveNovelHandler;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Novel\Enums\StoryStatus;
use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Exceptions\DomainException;
use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Follow;
use App\Models\Novel;
use App\Models\ReadingProgress;
use App\Models\SavedNovel;
use App\Models\User;

function libraryUser(): User
{
    return User::factory()->create();
}

function libraryPublishedNovel(): Novel
{
    return Novel::factory()->published()->create();
}

function libraryPublishedChapter(Novel $novel): Chapter
{
    return Chapter::factory()->for($novel, 'novel')->published()->create();
}

it('creates a reading progress factory row with a matching chapter novel', function () {
    $progress = ReadingProgress::factory()->create();

    expect($progress->chapter->novel_id)->toBe($progress->novel_id);
});

it('does not bookmark a published chapter when its novel is suspended', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    $novel->status = NovelStatus::Suspended;
    $novel->save();

    expect(fn () => app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand($user->id, $chapter->id)))
        ->toThrow(DomainException::class, 'Novel harus terbit sebelum dapat di-bookmark.');

    expect(Bookmark::query()
        ->where('user_id', $user->id)
        ->where('chapter_id', $chapter->id)
        ->exists())->toBeFalse();
});

it('follows a published novel idempotently', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $handler = app(FollowNovelHandler::class);

    $handler->handle(new FollowNovelCommand($user->id, $novel->id));
    $handler->handle(new FollowNovelCommand($user->id, $novel->id));

    expect(Follow::query()->where('user_id', $user->id)->where('novel_id', $novel->id)->count())->toBe(1)
        ->and(app(LibraryRepository::class)->isFollowing($user->id, $novel->id))->toBeTrue();
});

it('does not follow an unpublished novel', function () {
    $user = libraryUser();
    $novel = Novel::factory()->create();

    expect(fn () => app(FollowNovelHandler::class)->handle(new FollowNovelCommand($user->id, $novel->id)))
        ->toThrow(DomainException::class);

    expect(Follow::query()
        ->where('user_id', $user->id)
        ->where('novel_id', $novel->id)
        ->exists())->toBeFalse();
});

it('unfollows idempotently', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $library = app(LibraryRepository::class);
    $library->follow($user->id, $novel->id);

    app(UnfollowNovelHandler::class)->handle(new UnfollowNovelCommand($user->id, $novel->id));
    app(UnfollowNovelHandler::class)->handle(new UnfollowNovelCommand($user->id, $novel->id));

    expect($library->isFollowing($user->id, $novel->id))->toBeFalse();
});

it('saves and unsaves a published novel idempotently', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $handler = app(SaveNovelHandler::class);

    $handler->handle(new SaveNovelCommand($user->id, $novel->id));
    $handler->handle(new SaveNovelCommand($user->id, $novel->id));

    expect(SavedNovel::query()->where('user_id', $user->id)->where('novel_id', $novel->id)->count())->toBe(1);

    app(UnsaveNovelHandler::class)->handle(new UnsaveNovelCommand($user->id, $novel->id));
    app(UnsaveNovelHandler::class)->handle(new UnsaveNovelCommand($user->id, $novel->id));

    expect(app(LibraryRepository::class)->isSaved($user->id, $novel->id))->toBeFalse();
});

it('does not save an unpublished novel', function () {
    $user = libraryUser();
    $novel = Novel::factory()->create();

    expect(fn () => app(SaveNovelHandler::class)->handle(new SaveNovelCommand($user->id, $novel->id)))
        ->toThrow(DomainException::class);

    expect(SavedNovel::query()
        ->where('user_id', $user->id)
        ->where('novel_id', $novel->id)
        ->exists())->toBeFalse();
});

it('rejects negative reading progress positions', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    expect(fn () => app(SaveReadingProgressHandler::class)->handle(new SaveReadingProgressCommand(
        $user->id,
        $novel->id,
        $chapter->id,
        -1,
    )))->toThrow(DomainException::class, 'Posisi baca tidak valid.');

    expect(ReadingProgress::query()
        ->where('user_id', $user->id)
        ->where('novel_id', $novel->id)
        ->exists())->toBeFalse();
});

it('creates and updates one reading progress row per novel', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $firstChapter = libraryPublishedChapter($novel);
    $secondChapter = libraryPublishedChapter($novel);
    $handler = app(SaveReadingProgressHandler::class);

    $handler->handle(new SaveReadingProgressCommand($user->id, $novel->id, $firstChapter->id, 12));
    $handler->handle(new SaveReadingProgressCommand($user->id, $novel->id, $secondChapter->id, 42));

    $progress = ReadingProgress::query()->where('user_id', $user->id)->where('novel_id', $novel->id)->firstOrFail();

    expect(ReadingProgress::query()->count())->toBe(1)
        ->and($progress->chapter_id)->toBe($secondChapter->id)
        ->and($progress->position)->toBe(42)
        ->and($progress->last_read_at)->not->toBeNull();
});

it('rejects progress for a chapter from another novel', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $otherNovel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($otherNovel);

    expect(fn () => app(SaveReadingProgressHandler::class)->handle(new SaveReadingProgressCommand(
        $user->id,
        $novel->id,
        $chapter->id,
    )))->toThrow(DomainException::class);

    expect(ReadingProgress::query()
        ->where('user_id', $user->id)
        ->where('novel_id', $novel->id)
        ->exists())->toBeFalse();
});

it('rejects progress for an unpublished chapter', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = Chapter::factory()->for($novel, 'novel')->create();

    expect(fn () => app(SaveReadingProgressHandler::class)->handle(new SaveReadingProgressCommand(
        $user->id,
        $novel->id,
        $chapter->id,
    )))->toThrow(DomainException::class);
});

it('rejects negative bookmark positions', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    expect(fn () => app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand($user->id, $chapter->id, -1)))
        ->toThrow(DomainException::class, 'Posisi bookmark tidak valid.');

    expect(Bookmark::query()
        ->where('user_id', $user->id)
        ->where('chapter_id', $chapter->id)
        ->exists())->toBeFalse();
});

it('adds and removes bookmarks idempotently', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand($user->id, $chapter->id, 20, 'Catatan'));

    expect(app(LibraryRepository::class)->isBookmarked($user->id, $chapter->id))->toBeTrue();

    app(RemoveBookmarkHandler::class)->handle(new RemoveBookmarkCommand($user->id, $chapter->id));
    app(RemoveBookmarkHandler::class)->handle(new RemoveBookmarkCommand($user->id, $chapter->id));

    expect(app(LibraryRepository::class)->isBookmarked($user->id, $chapter->id))->toBeFalse();
});

it('allows multiple bookmarks for one user and chapter', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand($user->id, $chapter->id, 10));
    app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand($user->id, $chapter->id, 20));

    expect(Bookmark::query()->where('user_id', $user->id)->where('chapter_id', $chapter->id)->count())->toBe(2);
});

it('does not bookmark an unpublished chapter', function () {
    $user = libraryUser();
    $chapter = Chapter::factory()->create();

    expect(fn () => app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand($user->id, $chapter->id)))
        ->toThrow(DomainException::class);

    expect(Bookmark::query()
        ->where('user_id', $user->id)
        ->where('chapter_id', $chapter->id)
        ->exists())->toBeFalse();
});

it('does not progress an unpublished novel', function () {
    $user = libraryUser();
    $novel = Novel::factory()->create();
    $chapter = Chapter::factory()->for($novel, 'novel')->published()->create();

    expect(fn () => app(SaveReadingProgressHandler::class)->handle(new SaveReadingProgressCommand(
        $user->id,
        $novel->id,
        $chapter->id,
    )))->toThrow(DomainException::class);

    expect(ReadingProgress::query()
        ->where('user_id', $user->id)
        ->where('novel_id', $novel->id)
        ->exists())->toBeFalse();
});

it('redirects guests from library to login', function () {
    $this->get('/library')->assertRedirect(route('login'));
});

it('returns published library rows and tab counts', function () {
    $user = libraryUser();
    $followingAuthor = User::factory()->create(['name' => 'Penulis Ikuti']);
    $savedAuthor = User::factory()->create(['name' => 'Penulis Simpan']);
    $readingAuthor = User::factory()->create(['name' => 'Penulis Baca']);
    $finishedAuthor = User::factory()->create(['name' => 'Penulis Selesai']);

    $following = Novel::factory()->published()->create([
        'user_id' => $followingAuthor->id,
        'title' => 'Novel Diikuti',
    ]);
    $saved = Novel::factory()->published()->create([
        'user_id' => $savedAuthor->id,
        'title' => 'Novel Disimpan',
    ]);
    $reading = Novel::factory()->published()->create([
        'user_id' => $readingAuthor->id,
        'title' => 'Novel Dibaca',
    ]);
    $finished = Novel::factory()->published()->create([
        'user_id' => $finishedAuthor->id,
        'title' => 'Novel Selesai',
        'story_status' => StoryStatus::Completed->value,
    ]);

    $readingChapter = Chapter::factory()->published()->create([
        'novel_id' => $reading->id,
        'chapter_number' => 2,
    ]);
    $finishedChapter = Chapter::factory()->published()->create([
        'novel_id' => $finished->id,
        'chapter_number' => 3,
    ]);

    Follow::factory()->create(['user_id' => $user->id, 'novel_id' => $following->id]);
    SavedNovel::factory()->create(['user_id' => $user->id, 'novel_id' => $saved->id]);
    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'novel_id' => $reading->id,
        'chapter_id' => $readingChapter->id,
        'last_read_at' => now(),
    ]);
    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'novel_id' => $finished->id,
        'chapter_id' => $finishedChapter->id,
        'last_read_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)
        ->get('/library')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Library')
            ->where('activeTab', 'following')
            ->where('counts', [
                'following' => 1,
                'saved' => 1,
                'reading' => 2,
                'finished' => 1,
            ])
            ->where('items.0.id', $following->id)
        );

    $this->actingAs($user)
        ->get('/library?tab=saved')
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'saved')
            ->where('items.0.id', $saved->id)
        );

    $this->actingAs($user)
        ->get('/library?tab=reading')
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'reading')
            ->where('items.0.id', $reading->id)
            ->where('items.0.currentChapterNumber', 2)
            ->where('items.0.lastReadAt', fn ($value) => is_string($value))
        );

    $this->actingAs($user)
        ->get('/library?tab=finished')
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'finished')
            ->where('items.0.id', $finished->id)
            ->where('items.0.currentChapterNumber', 3)
        );
});

it('excludes unpublished novels from every library tab', function () {
    $user = libraryUser();
    $novel = Novel::factory()->create(['title' => 'Novel Tidak Terbit']);
    $chapter = libraryPublishedChapter($novel);

    Follow::factory()->create(['user_id' => $user->id, 'novel_id' => $novel->id]);
    SavedNovel::factory()->create(['user_id' => $user->id, 'novel_id' => $novel->id]);
    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
    ]);

    foreach (['following', 'saved', 'reading', 'finished'] as $tab) {
        $this->actingAs($user)
            ->get('/library?tab='.$tab)
            ->assertInertia(fn ($page) => $page
                ->where('items', fn ($items) => collect($items)
                    ->pluck('id')
                    ->doesntContain($novel->id))
            );
    }
});

it('paginates library rows and preserves the active tab in page links', function () {
    $user = libraryUser();
    $novels = Novel::factory()->published()->count(13)->create();

    foreach ($novels as $novel) {
        Follow::factory()->create([
            'user_id' => $user->id,
            'novel_id' => $novel->id,
        ]);
    }

    $this->actingAs($user)
        ->get('/library?tab=following')
        ->assertInertia(fn ($page) => $page
            ->where('items', fn ($items) => count($items) === 12)
            ->where('currentPage', 1)
            ->where('lastPage', 2)
            ->where('links', fn ($links) => collect($links)->contains(
                fn ($link) => is_string($link['url'])
                    && str_contains($link['url'], 'tab=following')
                    && str_contains($link['url'], 'page=2')
            ))
        );

    $this->actingAs($user)
        ->get('/library?tab=following&page=2')
        ->assertInertia(fn ($page) => $page
            ->where('items', fn ($items) => count($items) === 1)
            ->where('currentPage', 2)
        );
});

it('follows and saves published novels through post actions', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();

    $this->actingAs($user)
        ->post(route('novels.follow', ['novel' => $novel->id]))
        ->assertRedirect()
        ->assertSessionHas('success', 'Novel diikuti.');

    $this->assertDatabaseHas('follows', [
        'user_id' => $user->id,
        'novel_id' => $novel->id,
    ]);

    $this->actingAs($user)
        ->post(route('novels.save', ['novel' => $novel->id]))
        ->assertRedirect();

    $this->assertDatabaseHas('saved_novels', [
        'user_id' => $user->id,
        'novel_id' => $novel->id,
    ]);

    $this->actingAs($user)->post(route('novels.unfollow', ['novel' => $novel->id]));
    $this->actingAs($user)->post(route('novels.unsave', ['novel' => $novel->id]));

    expect(Follow::query()
        ->where('user_id', $user->id)
        ->where('novel_id', $novel->id)
        ->exists())->toBeFalse()
        ->and(SavedNovel::query()
            ->where('user_id', $user->id)
            ->where('novel_id', $novel->id)
            ->exists())->toBeFalse();
});

it('rejects follow and save actions for unpublished novels', function () {
    $user = libraryUser();
    $novel = Novel::factory()->create();

    $this->actingAs($user)
        ->post(route('novels.follow', ['novel' => $novel->id]))
        ->assertRedirect()
        ->assertSessionHas('error', 'Novel harus terbit sebelum dapat diikuti.');

    $this->actingAs($user)
        ->post(route('novels.save', ['novel' => $novel->id]))
        ->assertRedirect()
        ->assertSessionHas('error', 'Novel harus terbit sebelum dapat disimpan.');

    expect(Follow::query()->where('novel_id', $novel->id)->exists())->toBeFalse()
        ->and(SavedNovel::query()->where('novel_id', $novel->id)->exists())->toBeFalse();
});

it('shows current interaction and reading state on novel detail', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    app(FollowNovelHandler::class)->handle(new FollowNovelCommand($user->id, $novel->id));
    app(SaveNovelHandler::class)->handle(new SaveNovelCommand($user->id, $novel->id));
    app(SaveReadingProgressHandler::class)->handle(new SaveReadingProgressCommand(
        $user->id,
        $novel->id,
        $chapter->id,
    ));

    $this->actingAs($user)
        ->get(route('novels.show', ['novel' => $novel->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isFollowing', true)
            ->where('isSaved', true)
            ->where('readingProgress.chapterNumber', $chapter->chapter_number)
            ->where('readingProgress.lastReadAt', fn ($value) => is_string($value))
        );

    $this->post('/logout');

    $this->get(route('novels.show', ['novel' => $novel->slug]))
        ->assertInertia(fn ($page) => $page
            ->where('isFollowing', false)
            ->where('isSaved', false)
            ->where('readingProgress', null)
        );
});

it('returns no content for reader progress and creates the progress row', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    $this->actingAs($user)
        ->post(route('reader.progress'), [
            'novel_id' => $novel->id,
            'chapter_id' => $chapter->id,
        ])
        ->assertNoContent();

    $this->assertDatabaseHas('reading_progress', [
        'user_id' => $user->id,
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
    ]);
});

it('saves reader progress and bookmarks through post actions', function () {
    $user = libraryUser();
    $novel = libraryPublishedNovel();
    $chapter = libraryPublishedChapter($novel);

    $this->actingAs($user)
        ->get(route('novels.chapters.show', [
            'novel' => $novel->slug,
            'chapter' => $chapter->chapter_number,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isGuest', false)
            ->where('isBookmarked', false)
        );

    $this->actingAs($user)
        ->post(route('reader.progress'), [
            'novel_id' => $novel->id,
            'chapter_id' => $chapter->id,
        ])
        ->assertNoContent();

    $this->assertDatabaseHas('reading_progress', [
        'user_id' => $user->id,
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
        'position' => null,
    ]);

    $this->actingAs($user)
        ->post(route('reader.bookmark'), ['chapter_id' => $chapter->id])
        ->assertRedirect();

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'chapter_id' => $chapter->id,
    ]);

    $this->actingAs($user)
        ->post(route('reader.unbookmark'), ['chapter_id' => $chapter->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $user->id,
        'chapter_id' => $chapter->id,
    ]);
});
