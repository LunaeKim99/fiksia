<?php

namespace App\Http\Controllers;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $novels = Novel::query()
            ->where('status', NovelStatus::Published->value)
            ->with(['user.profile', 'genres'])
            ->withCount(['chapters as chapter_count' => fn ($query) => $query
                ->where('status', ChapterStatus::Published->value),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (Novel $novel): array => $this->serialize($novel));

        $user = $request->user();
        $continueReading = $user === null
            ? []
            : Novel::query()
                ->join('reading_progress', 'reading_progress.novel_id', '=', 'novels.id')
                ->join('chapters as progress_chapters', 'progress_chapters.id', '=', 'reading_progress.chapter_id')
                ->where('reading_progress.user_id', $user->id)
                ->where('novels.status', NovelStatus::Published->value)
                ->where('progress_chapters.status', ChapterStatus::Published->value)
                ->whereNull('progress_chapters.deleted_at')
                ->select('novels.*')
                ->addSelect([
                    DB::raw('reading_progress.last_read_at as progress_last_read_at'),
                    DB::raw('progress_chapters.chapter_number as current_chapter_number'),
                ])
                ->with(['user.profile', 'genres'])
                ->withCount(['chapters as chapter_count' => fn ($query) => $query
                    ->where('status', ChapterStatus::Published->value),
                ])
                ->orderByDesc('reading_progress.last_read_at')
                ->orderByDesc('reading_progress.id')
                ->limit(5)
                ->get()
                ->map(fn (Novel $novel): array => [
                    ...$this->serialize($novel),
                    'chapterNumber' => (int) $novel->getAttribute('current_chapter_number'),
                    'lastReadAt' => $this->dateString($novel->getAttribute('progress_last_read_at')),
                ]);

        return Inertia::render('Home', [
            'latestNovels' => $novels,
            'continueReading' => $continueReading,
        ]);
    }

    /** @return array<string, mixed> */
    private function serialize(Novel $novel): array
    {
        return [
            'id' => $novel->id,
            'title' => $novel->title,
            'slug' => $novel->slug,
            'coverAvailable' => $novel->cover_path !== null,
            'coverUrl' => $novel->cover_path === null ? null : route('novels.cover', ['novel' => $novel->id]),
            'story_status' => $novel->story_status->value,
            'story_status_label' => match ($novel->story_status->value) {
                'completed' => 'Selesai',
                'hiatus' => 'Tunda',
                default => 'Berlangsung',
            },
            'author' => [
                'name' => $novel->user->name,
                'username' => optional($novel->user->profile)->username ?? '—',
            ],
            'synopsis' => Str::limit((string) $novel->synopsis, 140),
            'chapterCount' => (int) $novel->getAttribute('chapter_count'),
            'genres' => $novel->genres->map(fn ($genre): array => [
                'id' => $genre->id,
                'name' => $genre->name,
                'slug' => $genre->slug,
            ])->values(),
        ];
    }

    private function dateString(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse((string) $value)->toIso8601String();
    }
}
