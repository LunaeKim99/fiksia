<?php

namespace App\Http\Controllers;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReaderController extends Controller
{
    public function __invoke(Request $request, string $novel, string $chapter): Response
    {
        $novelModel = Novel::query()
            ->where('slug', $novel)
            ->firstOrFail();

        abort_unless(Gate::allows('view', $novelModel), 404);

        $chapterModel = $novelModel->chapters()
            ->where('chapter_number', (int) $chapter)
            ->firstOrFail();

        abort_unless(Gate::allows('view', $chapterModel), 404);

        $chapters = $novelModel->chapters()
            ->where('status', ChapterStatus::Published->value)
            ->orderBy('chapter_number')
            ->get(['id', 'chapter_number', 'title']);

        $prevChapter = $chapters
            ->where('chapter_number', '<', $chapterModel->chapter_number)
            ->last();
        $nextChapter = $chapters
            ->where('chapter_number', '>', $chapterModel->chapter_number)
            ->first();

        $user = $request->user();

        return Inertia::render('Reader/Show', [
            'novel' => [
                'id' => $novelModel->id,
                'title' => $novelModel->title,
                'slug' => $novelModel->slug,
            ],
            'chapter' => [
                'id' => $chapterModel->id,
                'chapter_number' => $chapterModel->chapter_number,
                'title' => $chapterModel->title,
                'content' => (string) $chapterModel->content,
                'published_at' => $chapterModel->published_at?->toIso8601String(),
            ],
            'prevChapter' => $prevChapter?->chapter_number,
            'nextChapter' => $nextChapter?->chapter_number,
            'chapterList' => $chapters->map(fn (Chapter $item): array => [
                'id' => $item->id,
                'chapter_number' => $item->chapter_number,
                'title' => $item->title,
            ])->values(),
            'isOwner' => $user?->id === $novelModel->user_id,
            'isGuest' => $user === null,
            'isBookmarked' => $user !== null && Bookmark::query()
                ->where('user_id', $user->id)
                ->where('chapter_id', $chapterModel->id)
                ->exists(),
            'user' => $user?->only(['id', 'name']),
        ]);
    }
}
