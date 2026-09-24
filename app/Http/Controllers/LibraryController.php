<?php

namespace App\Http\Controllers;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Novel\Enums\StoryStatus;
use App\Models\Novel;
use App\Models\ReadingProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    private const TABS = ['following', 'saved', 'reading', 'finished'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $activeTab = in_array($request->query('tab'), self::TABS, true)
            ? (string) $request->query('tab')
            : 'following';

        $paginator = $this->novels($user->id, $activeTab)
            ->paginate(12)
            ->withQueryString();

        $items = $paginator->getCollection()
            ->map(fn (Novel $novel): array => $this->serialize(
                $novel,
                $this->attributeString($novel, 'progress_last_read_at'),
                $this->attributeInt($novel, 'current_chapter_number'),
            ))
            ->values();

        return Inertia::render('Library', [
            'activeTab' => $activeTab,
            'counts' => $this->counts($user->id),
            'items' => $items,
            'links' => $paginator->linkCollection()->values()->all(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ]);
    }

    /** @return array<string, int> */
    private function counts(int $userId): array
    {
        return [
            'following' => $this->publishedNovelQuery()
                ->whereHas('follows', fn (Builder $query): Builder => $query->where('user_id', $userId))
                ->count(),
            'saved' => $this->publishedNovelQuery()
                ->whereHas('savedNovels', fn (Builder $query): Builder => $query->where('user_id', $userId))
                ->count(),
            'reading' => $this->progressQuery($userId)->count(),
            'finished' => $this->progressQuery($userId)
                ->whereHas('novel', fn (Builder $query): Builder => $query->where('story_status', StoryStatus::Completed->value))
                ->count(),
        ];
    }

    /** @return Builder<Novel> */
    private function novels(int $userId, string $activeTab): Builder
    {
        $query = $this->publishedNovelQuery()
            ->with(['user.profile', 'genres'])
            ->select('novels.*')
            ->withCount(['chapters as chapter_count' => fn (Builder $chapterQuery): Builder => $chapterQuery
                ->where('chapters.status', ChapterStatus::Published->value),
            ]);

        if ($activeTab === 'following') {
            $query
                ->join('follows', 'follows.novel_id', '=', 'novels.id')
                ->where('follows.user_id', $userId)
                ->orderByDesc('follows.created_at')
                ->orderByDesc('novels.id');
        } elseif ($activeTab === 'saved') {
            $query
                ->join('saved_novels', 'saved_novels.novel_id', '=', 'novels.id')
                ->where('saved_novels.user_id', $userId)
                ->orderByDesc('saved_novels.created_at')
                ->orderByDesc('novels.id');
        } elseif ($activeTab === 'reading' || $activeTab === 'finished') {
            $query
                ->join('reading_progress', 'reading_progress.novel_id', '=', 'novels.id')
                ->join('chapters as progress_chapters', 'progress_chapters.id', '=', 'reading_progress.chapter_id')
                ->where('reading_progress.user_id', $userId)
                ->where('progress_chapters.status', ChapterStatus::Published->value)
                ->whereNull('progress_chapters.deleted_at')
                ->addSelect([
                    DB::raw('reading_progress.last_read_at as progress_last_read_at'),
                    DB::raw('progress_chapters.chapter_number as current_chapter_number'),
                ])
                ->orderByDesc('reading_progress.last_read_at')
                ->orderByDesc('reading_progress.id');

            if ($activeTab === 'finished') {
                $query->where('novels.story_status', StoryStatus::Completed->value);
            }
        }

        return $query;
    }

    /** @return Builder<Novel> */
    private function publishedNovelQuery(): Builder
    {
        return Novel::query()->where('novels.status', NovelStatus::Published->value);
    }

    /** @return Builder<ReadingProgress> */
    private function progressQuery(int $userId): Builder
    {
        return ReadingProgress::query()
            ->where('user_id', $userId)
            ->whereHas('novel', fn (Builder $query): Builder => $query->where('status', NovelStatus::Published->value))
            ->whereHas('chapter', fn (Builder $query): Builder => $query->where('status', ChapterStatus::Published->value));
    }

    /** @return array<string, mixed> */
    private function serialize(Novel $novel, ?string $lastReadAt, ?int $currentChapterNumber): array
    {
        return [
            'id' => $novel->id,
            'title' => $novel->title,
            'slug' => $novel->slug,
            'coverAvailable' => $novel->cover_path !== null,
            'coverUrl' => $novel->cover_path === null ? null : route('novels.cover', ['novel' => $novel->id]),
            'synopsis' => Str::limit((string) $novel->synopsis, 140),
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
            'genres' => $novel->genres->map(fn ($genre): array => [
                'id' => $genre->id,
                'name' => $genre->name,
                'slug' => $genre->slug,
            ])->values(),
            'chapterCount' => (int) $novel->getAttribute('chapter_count'),
            'lastReadAt' => $lastReadAt === null ? null : Carbon::parse($lastReadAt)->toIso8601String(),
            'currentChapterNumber' => $currentChapterNumber,
        ];
    }

    private function attributeString(Novel $novel, string $attribute): ?string
    {
        $value = $novel->getAttribute($attribute);

        return $value === null ? null : (string) $value;
    }

    private function attributeInt(Novel $novel, string $attribute): ?int
    {
        $value = $novel->getAttribute($attribute);

        return $value === null ? null : (int) $value;
    }
}
