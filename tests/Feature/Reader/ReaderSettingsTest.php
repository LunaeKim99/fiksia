<?php

use App\Models\Chapter;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function readerSettingsNovel(): Novel
{
    return Novel::factory()->published()->create();
}

function readerSettingsChapter(Novel $novel): Chapter
{
    return Chapter::factory()->published()->create([
        'novel_id' => $novel->id,
        'chapter_number' => 1,
    ]);
}

it('shares normalized reader settings for authenticated users', function () {
    $user = User::factory()->create();
    $novel = readerSettingsNovel();
    $chapter = readerSettingsChapter($novel);

    $user->settings()->create([
        'appearance' => [
            'mode' => 'dark',
            'accentStyle' => 'gradient',
            'preset' => 'custom',
            'primaryColor' => '#0E7490',
            'secondaryColor' => '#2563EB',
        ],
        'reading' => [
            'fontSize' => 'large',
            'fontFamily' => 'serif',
            'lineHeight' => '2.0',
            'columnWidth' => 'wide',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('novels.chapters.show', [
            'novel' => $novel->slug,
            'chapter' => $chapter->chapter_number,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reader/Show')
            ->where('isGuest', false)
            ->where('appearanceSettings.mode', 'dark')
            ->where('appearanceSettings.accentStyle', 'gradient')
            ->where('appearanceSettings.preset', 'custom')
            ->where('appearanceSettings.primaryColor', '#0E7490')
            ->where('appearanceSettings.secondaryColor', '#2563EB')
            ->where('readingSettings.fontSize', 'large')
            ->where('readingSettings.fontFamily', 'serif')
            ->where('readingSettings.lineHeight', '2.0')
            ->where('readingSettings.columnWidth', 'wide')
        );
});

it('uses null server settings for guest readers', function () {
    $novel = readerSettingsNovel();
    $chapter = readerSettingsChapter($novel);

    $this->get(route('novels.chapters.show', [
        'novel' => $novel->slug,
        'chapter' => $chapter->chapter_number,
    ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reader/Show')
            ->where('isGuest', true)
            ->where('appearanceSettings', null)
            ->where('readingSettings', null)
        );
});

it('shows a saved reading section when returning to the reader', function () {
    $user = User::factory()->create();
    $novel = readerSettingsNovel();
    $chapter = readerSettingsChapter($novel);

    $this->actingAs($user)
        ->from(route('settings.show'))
        ->patch(route('settings.update', ['section' => 'reading']), [
            'fontSize' => 'large',
            'fontFamily' => 'serif',
            'lineHeight' => '2.0',
            'columnWidth' => 'wide',
        ])
        ->assertRedirect(route('settings.show'));

    $this->actingAs($user)
        ->get(route('novels.chapters.show', [
            'novel' => $novel->slug,
            'chapter' => $chapter->chapter_number,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('readingSettings.fontSize', 'large')
            ->where('readingSettings.fontFamily', 'serif')
            ->where('readingSettings.lineHeight', '2.0')
            ->where('readingSettings.columnWidth', 'wide')
        );
});
