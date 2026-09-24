<?php

use App\Domain\Novel\Enums\NovelStatus;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Novel;
use App\Models\Profile;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('shows published novels to guests on home', function () {
    $author = User::factory()->create(['name' => 'Penulis Publik']);
    Profile::factory()->create(['user_id' => $author->id, 'username' => 'penulispublik']);
    $novel = Novel::factory()->published()->create([
        'user_id' => $author->id,
        'title' => 'Cerita Publik',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home')
            ->where('latestNovels.0.id', $novel->id)
            ->where('latestNovels.0.title', 'Cerita Publik')
            ->where('latestNovels.0.coverAvailable', false)
            ->where('latestNovels.0.author.name', 'Penulis Publik')
            ->where('latestNovels.0.author.username', 'penulispublik')
        );
});

it('shows home empty state when no published novels exist', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home')
            ->where('latestNovels', [])
            ->where('continueReading', [])
        );
});

it('shows continue reading for authenticated users', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $novel = Novel::factory()->published()->create([
        'user_id' => $author->id,
        'title' => 'Novel Lanjut Baca',
    ]);
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 4,
    ]);
    ReadingProgress::factory()->create([
        'user_id' => $user->id,
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
        'last_read_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('continueReading.0.id', $novel->id)
            ->where('continueReading.0.chapterNumber', 4)
            ->where('continueReading.0.lastReadAt', fn ($value) => is_string($value))
        );
});

it('finds published novels independently by title synopsis and author', function () {
    $titleMatch = Novel::factory()->published()->create([
        'title' => 'Cerita dengan JudulKhas',
        'synopsis' => 'Ringkasan biasa',
    ]);
    $synopsisMatch = Novel::factory()->published()->create([
        'title' => 'Cerita dengan Ringkasan',
        'synopsis' => 'RingkasanKhas di sini',
    ]);
    $author = User::factory()->create(['name' => 'PenulisKhas']);
    Profile::factory()->create(['user_id' => $author->id, 'username' => 'penulisKhas']);
    $authorMatch = Novel::factory()->published()->create([
        'user_id' => $author->id,
        'title' => 'Cerita penulis',
        'synopsis' => 'Ringkasan biasa',
    ]);

    $assertMatch = function (string $term, Novel $expected): void {
        $this->get('/explore?q='.urlencode($term))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Explore')
                ->where('totalResults', 1)
                ->where('novels.data.0.id', $expected->id)
            );
    };

    $assertMatch('JudulKhas', $titleMatch);
    $assertMatch('RingkasanKhas', $synopsisMatch);
    $assertMatch('PenulisKhas', $authorMatch);
    $assertMatch('penulisKhas', $authorMatch);
});

it('filters published novels by genre and story status', function () {
    $genre = Genre::factory()->create(['name' => 'Fantasi', 'slug' => 'fantasi']);
    $match = Novel::factory()->published()->create([
        'title' => 'Novel Fantasi Selesai',
        'story_status' => 'completed',
    ]);
    $match->genres()->attach($genre);
    Novel::factory()->published()->create([
        'title' => 'Novel Fantasi Berlangsung',
        'story_status' => 'ongoing',
    ])->genres()->attach($genre);

    $this->get('/explore?genre=fantasi&status=completed')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Explore')
            ->where('totalResults', 1)
            ->where('activeFilters.genre', 'fantasi')
            ->where('activeFilters.status', 'completed')
            ->where('novels.data.0.id', $match->id)
        );
});

it('never exposes unpublished or soft deleted novels in catalog pages', function () {
    $draft = Novel::factory()->create(['title' => 'Draft Catalog']);
    $rejected = Novel::factory()->create(['status' => NovelStatus::Rejected->value, 'title' => 'Rejected Catalog']);
    $suspended = Novel::factory()->create(['status' => NovelStatus::Suspended->value, 'title' => 'Suspended Catalog']);
    $deleted = Novel::factory()->published()->create(['title' => 'Deleted Catalog']);
    $deleted->delete();

    $this->get('/explore')->assertOk()->assertDontSee($draft->title);
    $this->get('/explore')->assertOk()->assertDontSee($rejected->title);
    $this->get('/explore')->assertOk()->assertDontSee($suspended->title);
    $this->get('/explore')->assertOk()->assertDontSee($deleted->title);
    $this->get('/')->assertOk()->assertDontSee($draft->title);
    $this->get('/')->assertOk()->assertDontSee($rejected->title);
    $this->get('/')->assertOk()->assertDontSee($suspended->title);
    $this->get('/')->assertOk()->assertDontSee($deleted->title);
});

it('shows published novel detail and hides unpublished chapter data', function () {
    $novel = Novel::factory()->published()->create(['title' => 'Novel Detail']);
    Chapter::factory()->published()->create(['novel_id' => $novel->id, 'chapter_number' => 1, 'title' => 'Bab Terbit']);
    Chapter::factory()->create(['novel_id' => $novel->id, 'chapter_number' => 2, 'title' => 'Rahasia Draft']);

    $this->get("/novels/{$novel->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Novel/Show')
            ->where('novel.title', 'Novel Detail')
            ->where('novel.chapters.0.title', 'Bab Terbit')
            ->where('novel.chapters', fn ($chapters) => count($chapters) === 1)
            ->where('isOwner', false)
        )
        ->assertDontSee('Rahasia Draft');
});

it('returns 404 for non published or missing novel details', function (string $status, bool $deleted = false) {
    $novel = Novel::factory()->create(['status' => $status]);

    if ($deleted) {
        $novel->delete();
    }

    $this->get("/novels/{$novel->slug}")->assertNotFound();
})->with([
    'draft' => ['draft'],
    'rejected' => ['rejected'],
    'suspended' => ['suspended'],
    'archived deleted' => ['archived', true],
]);

it('returns 404 for a nonexistent novel detail', function () {
    $this->get('/novels/novel-yang-tidak-ada')->assertNotFound();
});

it('reads published chapter for guest and rejects unpublished chapter or novel', function () {
    $novel = Novel::factory()->published()->create(['title' => 'Novel Reader']);
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 1,
        'title' => 'Bab Reader',
        'content' => "Baris pertama.\nBaris kedua.",
    ]);
    Chapter::factory()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 2,
        'title' => 'Draft Reader',
    ]);

    $this->get("/novels/{$novel->slug}/chapters/{$chapter->chapter_number}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reader/Show')
            ->where('chapter.content', "Baris pertama.\nBaris kedua.")
            ->where('chapterList.0.title', 'Bab Reader')
            ->where('isGuest', true)
            ->where('isBookmarked', false)
        );

    $this->get("/novels/{$novel->slug}/chapters/2")->assertNotFound();
    $draftNovel = Novel::factory()->create(['title' => 'Draft Novel Reader']);
    $this->get("/novels/{$draftNovel->slug}/chapters/1")->assertNotFound();

    $deletedNovel = Novel::factory()->published()->create(['title' => 'Deleted Novel Reader']);
    $deletedNovel->delete();
    $this->get("/novels/{$deletedNovel->slug}/chapters/1")->assertNotFound();

    $deletedChapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 3,
        'title' => 'Deleted Chapter Reader',
    ]);
    $deletedChapter->delete();
    $this->get("/novels/{$novel->slug}/chapters/3")->assertNotFound();
});

it('serves published covers publicly and rejects unpublished covers for guests', function () {
    Storage::fake('local');
    Storage::disk('local')->put('covers/public.jpg', 'image');

    $published = Novel::factory()->published()->create(['cover_path' => 'covers/public.jpg']);
    $unpublished = Novel::factory()->create(['cover_path' => 'covers/public.jpg']);

    $this->get("/novels/{$published->id}/cover")->assertOk();
    $this->get("/novels/{$unpublished->id}/cover")->assertForbidden();

    $missingUnpublished = Novel::factory()->create([
        'cover_path' => 'covers/missing.jpg',
    ]);
    $this->get("/novels/{$missingUnpublished->id}/cover")->assertForbidden();
});

it('paginates published explore results and preserves filters in page links', function () {
    $genre = Genre::factory()->create(['name' => 'Fantasi', 'slug' => 'fantasi']);
    $novels = Novel::factory()->published()->count(13)->create([
        'title' => 'Novel FilterKhas',
        'story_status' => 'ongoing',
    ]);
    $novels->each(fn (Novel $novel) => $novel->genres()->attach($genre));

    $this->get('/explore?q=FilterKhas&genre=fantasi&status=ongoing&sort=popular')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('totalResults', 13)
            ->where('novels.data', fn ($data) => $data->count() === 12)
            ->where('novels.links.2.url', fn ($url) => is_string($url)
                && str_contains($url, 'q=FilterKhas')
                && str_contains($url, 'genre=fantasi')
                && str_contains($url, 'status=ongoing')
                && str_contains($url, 'sort=popular')
                && str_contains($url, 'page=2'))
        );
});
