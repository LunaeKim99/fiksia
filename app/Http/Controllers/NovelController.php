<?php

namespace App\Http\Controllers;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\Follow;
use App\Models\Novel;
use App\Models\ReadingProgress;
use App\Models\SavedNovel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NovelController extends Controller
{
    public function show(Request $request, string $novel): Response
    {
        $model = Novel::query()
            ->where('slug', $novel)
            ->with(['user.profile', 'genres'])
            ->firstOrFail();

        abort_unless(Gate::allows('view', $model), 404);

        $chapters = $model->chapters()
            ->where('status', ChapterStatus::Published->value)
            ->orderBy('chapter_number')
            ->get(['id', 'chapter_number', 'title', 'status', 'is_free', 'published_at'])
            ->map(fn (Chapter $chapter): array => [
                'id' => $chapter->id,
                'chapter_number' => $chapter->chapter_number,
                'title' => $chapter->title,
                'is_free' => $chapter->is_free,
                'published_at' => $chapter->published_at?->toIso8601String(),
                'status' => $chapter->status->value,
            ])
            ->values();

        $user = $request->user();
        $progress = $user === null
            ? null
            : ReadingProgress::query()
                ->where('user_id', $user->id)
                ->where('novel_id', $model->id)
                ->whereHas('chapter', fn ($query) => $query->where('status', ChapterStatus::Published->value))
                ->with('chapter:id,chapter_number')
                ->first();

        return Inertia::render('Novel/Show', [
            'novel' => [
                'id' => $model->id,
                'title' => $model->title,
                'slug' => $model->slug,
                'coverAvailable' => $model->cover_path !== null,
                'coverUrl' => $model->cover_path === null ? null : route('novels.cover', ['novel' => $model->id]),
                'synopsis' => $model->synopsis,
                'story_status' => $model->story_status->value,
                'story_status_label' => match ($model->story_status->value) {
                    'completed' => 'Selesai',
                    'hiatus' => 'Tunda',
                    default => 'Berlangsung',
                },
                'author' => [
                    'id' => $model->user->id,
                    'name' => $model->user->name,
                    'username' => optional($model->user->profile)->username ?? '—',
                ],
                'genres' => $model->genres->map(fn ($genre): array => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'slug' => $genre->slug,
                ])->values(),
                'published_at' => $model->published_at?->toIso8601String(),
                'chapterCount' => $chapters->count(),
                'chapters' => $chapters,
            ],
            'isOwner' => $user?->id === $model->user_id,
            'isFollowing' => $user !== null && Follow::query()
                ->where('user_id', $user->id)
                ->where('novel_id', $model->id)
                ->exists(),
            'isSaved' => $user !== null && SavedNovel::query()
                ->where('user_id', $user->id)
                ->where('novel_id', $model->id)
                ->exists(),
            'readingProgress' => $progress?->chapter === null ? null : [
                'chapterNumber' => $progress->chapter->chapter_number,
                'lastReadAt' => $progress->last_read_at?->toIso8601String(),
            ],
            'latestChapterId' => $chapters->last()['id'] ?? null,
        ]);
    }
}
