<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\Moderation\ModerationQueueController;
use App\Http\Controllers\NovelController;
use App\Http\Controllers\NovelCoverController;
use App\Http\Controllers\NovelInteractionController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\ReaderInteractionController;
use App\Http\Controllers\Settings\AccountSettingsController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Studio\StudioChapterController;
use App\Http\Controllers\Studio\StudioController;
use App\Http\Controllers\Studio\StudioNovelController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/explore', ExploreController::class)->name('explore');
Route::get('/studio', [StudioController::class, 'index'])->name('studio');
Route::get('/account', fn () => inertia('Account'))->name('account');

Route::get('/novels/{novel}/cover', NovelCoverController::class)->name('novels.cover');
Route::get('/novels/{novel}', [NovelController::class, 'show'])->name('novels.show');
Route::get('/novels/{novel}/chapters/{chapter}', ReaderController::class)->name('novels.chapters.show');

Route::middleware(['guest'])->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:register');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

Route::get('/settings/account/google/connect', [GoogleAuthController::class, 'connect'])
    ->middleware(['auth', 'active'])
    ->name('settings.account.google.connect');

Route::post('/settings/account/google/disconnect', [GoogleAuthController::class, 'disconnect'])
    ->middleware(['auth', 'active'])
    ->name('settings.account.google.disconnect');

Route::get('/settings', [SettingsController::class, 'show'])
    ->middleware(['auth', 'active'])
    ->name('settings.show');

Route::patch('/settings/account/profile', [
    AccountSettingsController::class,
    'updateProfile',
])
    ->middleware(['auth', 'active'])
    ->name('settings.account.profile.update');

Route::patch('/settings/{section}', [SettingsController::class, 'update'])
    ->whereIn('section', ['appearance', 'reading', 'privacy', 'notifications'])
    ->middleware(['auth', 'active'])
    ->name('settings.update');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/library', [LibraryController::class, 'index'])->name('library');

    Route::post('/novels/{novel}/follow', [NovelInteractionController::class, 'follow'])->name('novels.follow');
    Route::post('/novels/{novel}/unfollow', [NovelInteractionController::class, 'unfollow'])->name('novels.unfollow');
    Route::post('/novels/{novel}/save', [NovelInteractionController::class, 'save'])->name('novels.save');
    Route::post('/novels/{novel}/unsave', [NovelInteractionController::class, 'unsave'])->name('novels.unsave');

    Route::post('/reader/progress', [ReaderInteractionController::class, 'progress'])
        ->middleware('throttle:reader-progress')
        ->name('reader.progress');
    Route::post('/reader/bookmark', [ReaderInteractionController::class, 'bookmark'])->name('reader.bookmark');
    Route::post('/reader/unbookmark', [ReaderInteractionController::class, 'unbookmark'])->name('reader.unbookmark');

    Route::get('/moderation/queue', [ModerationQueueController::class, 'index'])->name('moderation.queue');
    Route::get('/moderation/submissions/{submission}', [ModerationQueueController::class, 'show'])->name('moderation.submissions.show');
    Route::post('/moderation/submissions/{submission}', [ModerationQueueController::class, 'decide'])
        ->middleware('throttle:moderation-decision')
        ->name('moderation.submissions.decide');

    Route::get('/studio/novels/create', [StudioNovelController::class, 'create'])->name('studio.novels.create');
    Route::post('/studio/novels', [StudioNovelController::class, 'store'])->name('studio.novels.store');
    Route::get('/studio/novels/{novel}', [StudioNovelController::class, 'show'])->name('studio.novels.show');
    Route::post('/studio/novels/{novel}/submit-review', [StudioNovelController::class, 'submitReview'])
        ->middleware('throttle:submit-review')
        ->name('studio.novels.submitReview');

    Route::get('/studio/novels/{novel}/chapters/create', [StudioChapterController::class, 'create'])->name('studio.chapters.create');
    Route::post('/studio/novels/{novel}/chapters', [StudioChapterController::class, 'store'])->name('studio.chapters.store');

    Route::get('/studio/chapters/{chapter}/edit', [StudioChapterController::class, 'edit'])->name('studio.chapters.edit');
    Route::post('/studio/chapters/{chapter}', [StudioChapterController::class, 'update'])->name('studio.chapters.update');
    Route::post('/studio/chapters/{chapter}/publish', [StudioChapterController::class, 'publish'])->name('studio.chapters.publish');
});
