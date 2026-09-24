<?php

use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shows studio dashboard to authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/studio')->assertOk();
});

it('shows guest studio dashboard with empty novels list', function () {
    $this->get('/studio')->assertOk();
});

it('shows studio dashboard with user novels', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id, 'title' => 'Novel Uji']);

    $this->actingAs($user)
        ->get('/studio')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Studio/Index'));
});

it('allows creating a novel with valid data and genres', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $genre = Genre::firstOrCreate(
        ['name' => 'Fantasi'],
        ['slug' => 'fantasi', 'status' => 'active'],
    );

    $response = $this->actingAs($user)->post('/studio/novels', [
        'title' => 'Novel Baru',
        'synopsis' => 'Sinopsis novel.',
        'story_status' => 'ongoing',
        'genreIds' => [$genre->id],
    ]);

    $response->assertRedirect('/studio/novels/1');
    $this->assertDatabaseHas('novels', [
        'user_id' => $user->id,
        'title' => 'Novel Baru',
        'slug' => 'novel-baru',
        'story_status' => 'ongoing',
        'status' => 'draft',
    ]);
    $this->assertDatabaseHas('novel_genres', [
        'novel_id' => Novel::where('title', 'Novel Baru')->first()->id,
        'genre_id' => $genre->id,
    ]);
});

it('rejects novel creation for guests', function () {
    $this->post('/studio/novels', [
        'title' => 'Novel Tanpa Login',
        'story_status' => 'ongoing',
    ])->assertRedirect('/login');
});

it('returns 403 when non-owner views novel details', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder)->get("/studio/novels/{$novel->id}")->assertForbidden();
});

it('shows novel details to owner', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/studio/novels/{$novel->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Studio/Novels/Show'));
});

it('creates chapters with auto-incrementing numbers', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post("/studio/novels/{$novel->id}/chapters", [
        'title' => 'Bab Satu',
        'content' => 'Isi bab satu.',
    ])->assertRedirect();

    $chapterOne = Chapter::where('novel_id', $novel->id)->where('title', 'Bab Satu')->first();
    expect($chapterOne->chapter_number)->toBe(1);

    $this->actingAs($user)->post("/studio/novels/{$novel->id}/chapters", [
        'title' => 'Bab Dua',
        'content' => 'Isi bab dua.',
    ])->assertRedirect();

    $chapterTwo = Chapter::where('novel_id', $novel->id)->where('title', 'Bab Dua')->first();
    expect($chapterTwo->chapter_number)->toBe(2);
});

it('updates a draft chapter as owner', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Lama',
        'content' => 'Isi lama',
        'status' => 'draft',
    ]);

    $this->actingAs($user)->post("/studio/chapters/{$chapter->id}", [
        'title' => 'Bab Diperbarui',
        'content' => 'Isi baru.',
    ])->assertRedirect();

    $this->assertDatabaseHas('chapters', [
        'id' => $chapter->id,
        'title' => 'Bab Diperbarui',
        'content' => 'Isi baru.',
    ]);
});

it('returns 403 when non-owner updates a draft chapter', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $owner->id]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Milik Orang Lain',
        'content' => 'Isi',
        'status' => 'draft',
    ]);

    $this->actingAs($intruder)->post("/studio/chapters/{$chapter->id}", [
        'title' => 'Bab Dihack',
        'content' => 'Isi hack',
    ])->assertForbidden();
});

it('blocks update of published chapter with error flash', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id, 'status' => 'published']);
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Terbit',
    ]);

    $this->actingAs($user)->post("/studio/chapters/{$chapter->id}", [
        'title' => 'Bab Edit',
        'content' => 'Coba edit.',
    ])->assertForbidden();

    $this->assertDatabaseHas('chapters', [
        'id' => $chapter->id,
        'title' => 'Bab Terbit',
    ]);
});

it('submits a novel for initial review with chapter IDs', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Uji',
        'content' => 'Isi',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->post("/studio/novels/{$novel->id}/submit-review", [
            'chapter_ids' => [$chapter->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('initial_submissions', [
        'novel_id' => $novel->id,
        'submitted_by' => $user->id,
        'status' => 'submitted',
    ]);
    $this->assertDatabaseHas('novels', [
        'id' => $novel->id,
        'status' => 'initial_review',
    ]);
});

it('returns 403 when non-owner submits review', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $owner->id, 'status' => 'draft']);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Uji',
        'content' => 'Isi',
        'status' => 'draft',
    ]);

    $this->actingAs($intruder)
        ->post("/studio/novels/{$novel->id}/submit-review", [
            'chapter_ids' => [$chapter->id],
        ])
        ->assertForbidden();
});

it('publishes a chapter after novel is approved', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->published()->create(['user_id' => $user->id]);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Publish',
        'status' => 'draft',
    ]);

    $this->actingAs($user)->post("/studio/chapters/{$chapter->id}/publish")->assertRedirect();

    $this->assertDatabaseHas('chapters', [
        'id' => $chapter->id,
        'status' => 'published',
    ]);
    expect($chapter->fresh()->published_at)->not->toBeNull();
});

it('blocks publishing a chapter while novel is still in draft', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    $chapter = Chapter::factory()->create([
        'novel_id' => $novel->id,
        'title' => 'Bab Draft',
        'status' => 'draft',
    ]);

    $this->actingAs($user)->post("/studio/chapters/{$chapter->id}/publish")->assertForbidden();

    $this->assertDatabaseHas('chapters', [
        'id' => $chapter->id,
        'status' => 'draft',
    ]);
    expect($chapter->fresh()->published_at)->toBeNull();
});

it('stores a valid cover image under covers with uuid filename', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('cover.jpg', 100, 100);

    $this->actingAs($user)->post('/studio/novels', [
        'title' => 'Novel Sampul',
        'story_status' => 'ongoing',
        'cover' => $file,
    ])->assertRedirect();

    $novel = Novel::where('user_id', $user->id)->first();
    expect($novel->cover_path)->not->toBeNull();
    expect($novel->cover_path)->toStartWith('covers/');
    expect($novel->cover_path)->toContain('jpg');
    Storage::disk('local')->assertExists($novel->cover_path);
});

it('rejects cover upload with invalid extension', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post('/studio/novels', [
            'title' => 'Novel PDF',
            'story_status' => 'ongoing',
            'cover' => $file,
        ])
        ->assertSessionHasErrors(['cover']);
});

it('rejects cover upload exceeding max size', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('cover.jpg', 200, 200)->size(3000);

    $this->actingAs($user)
        ->post('/studio/novels', [
            'title' => 'Novel Besar',
            'story_status' => 'ongoing',
            'cover' => $file,
        ])
        ->assertSessionHasErrors(['cover']);
});

it('rejects creating a chapter with empty title', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post("/studio/novels/{$novel->id}/chapters", [
            'title' => '',
            'content' => 'Isi.',
        ])
        ->assertSessionHasErrors(['title']);
});

it('rejects submitting review without chapter IDs', function () {
    $user = User::factory()->create();
    $novel = Novel::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user)
        ->post("/studio/novels/{$novel->id}/submit-review", [
            'chapter_ids' => [],
        ])
        ->assertSessionHasErrors(['chapter_ids']);
});
