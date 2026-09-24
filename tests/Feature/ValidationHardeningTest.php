<?php

use App\Models\Chapter;
use App\Models\Novel;
use App\Models\User;

function validationUser(): User
{
    return User::factory()->create();
}

it('trims novel title and synopsis before creating a novel', function () {
    $user = validationUser();

    $this->actingAs($user)->post(route('studio.novels.store'), [
        'title' => '  Novel Bersih  ',
        'synopsis' => '  Sinopsis bersih.  ',
        'story_status' => 'ongoing',
    ])->assertRedirect();

    $this->assertDatabaseHas('novels', [
        'user_id' => $user->id,
        'title' => 'Novel Bersih',
        'synopsis' => 'Sinopsis bersih.',
    ]);
});

it('rejects invalid novel story status', function () {
    $user = validationUser();

    $this->actingAs($user)
        ->post(route('studio.novels.store'), [
            'title' => 'Novel Status Salah',
            'story_status' => 'unknown',
        ])
        ->assertSessionHasErrors('story_status');
});

it('validates and stores reading progress position', function () {
    $user = validationUser();
    $novel = Novel::factory()->published()->create();
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
    ]);

    $this->actingAs($user)->post(route('reader.progress'), [
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
        'position' => -1,
    ])->assertSessionHasErrors('position');

    $this->actingAs($user)->post(route('reader.progress'), [
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
        'position' => 42,
    ])->assertNoContent();

    $this->assertDatabaseHas('reading_progress', [
        'user_id' => $user->id,
        'novel_id' => $novel->id,
        'chapter_id' => $chapter->id,
        'position' => 42,
    ]);
});

it('validates and stores bookmark position', function () {
    $user = validationUser();
    $novel = Novel::factory()->published()->create();
    $chapter = Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
    ]);

    $this->actingAs($user)->post(route('reader.bookmark'), [
        'chapter_id' => $chapter->id,
        'position' => -1,
    ])->assertSessionHasErrors('position');

    $this->actingAs($user)->post(route('reader.bookmark'), [
        'chapter_id' => $chapter->id,
        'position' => 24,
    ])->assertRedirect();

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'chapter_id' => $chapter->id,
        'position' => 24,
    ]);
});
