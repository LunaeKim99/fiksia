<?php

namespace App\Providers;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Moderation\Contracts\InitialSubmissionRepository;
use App\Domain\Moderation\Contracts\ModerationActionRepository;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Shared\Contracts\UserRepository;
use App\Infrastructure\Persistence\Repositories\EloquentChapterRepository;
use App\Infrastructure\Persistence\Repositories\EloquentInitialSubmissionRepository;
use App\Infrastructure\Persistence\Repositories\EloquentLibraryRepository;
use App\Infrastructure\Persistence\Repositories\EloquentModerationActionRepository;
use App\Infrastructure\Persistence\Repositories\EloquentNovelRepository;
use App\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NovelRepository::class, EloquentNovelRepository::class);
        $this->app->bind(ChapterRepository::class, EloquentChapterRepository::class);
        $this->app->bind(InitialSubmissionRepository::class, EloquentInitialSubmissionRepository::class);
        $this->app->bind(ModerationActionRepository::class, EloquentModerationActionRepository::class);
        $this->app->bind(LibraryRepository::class, EloquentLibraryRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perHour(30)->by($request->ip());
        });

        RateLimiter::for('submit-review', function (Request $request): Limit {
            return Limit::perMinute(10)->by('submit-review:'.(string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('moderation-decision', function (Request $request): Limit {
            return Limit::perMinute(30)->by('moderation-decision:'.(string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('reader-progress', function (Request $request): Limit {
            return Limit::perMinute(60)->by('reader-progress:'.(string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
