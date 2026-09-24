<?php

namespace App\Http\Controllers\Studio;

use App\Application\Moderation\Commands\SubmitInitialReview\SubmitInitialReviewCommand;
use App\Application\Moderation\Commands\SubmitInitialReview\SubmitInitialReviewHandler;
use App\Application\Novel\Commands\CreateNovel\CreateNovelCommand;
use App\Application\Novel\Commands\CreateNovel\CreateNovelHandler;
use App\Domain\Novel\Enums\StoryStatus;
use App\Domain\Shared\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreNovelRequest;
use App\Http\Requests\Studio\SubmitReviewRequest;
use App\Models\Genre;
use App\Models\Novel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudioNovelController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Novel::class);

        return Inertia::render('Studio/Novels/Create', [
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreNovelRequest $request): RedirectResponse
    {
        Gate::authorize('create', Novel::class);

        $user = $request->user();
        $coverPath = null;
        $coverFile = $request->file('cover');

        if ($coverFile !== null) {
            $stored = $coverFile->storeAs(
                'covers',
                Str::uuid()->toString().'.'.($coverFile->guessExtension() ?? 'jpg'),
                'local',
            );
            $coverPath = $stored === false ? null : $stored;
        }

        try {
            $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
                userId: $user->id,
                title: $request->string('title')->toString(),
                synopsis: $request->input('synopsis'),
                coverPath: $coverPath,
                storyStatus: StoryStatus::from($request->string('story_status')->toString()),
                genreIds: array_map('intval', $request->input('genreIds', [])),
                tagIds: array_map('intval', $request->input('tagIds', [])),
            ));
        } catch (DomainException $e) {
            if ($coverPath !== null) {
                Storage::disk('local')->delete($coverPath);
            }

            return Redirect::back()->with('error', $e->getMessage());
        }

        return Redirect::route('studio.novels.show', ['novel' => $novel->id])
            ->with('success', 'Novel berhasil dibuat.');
    }

    public function show(Request $request, Novel $novel): Response
    {
        Gate::authorize('viewForAuthor', $novel);

        $novel->load(['genres', 'initialSubmissions']);

        $chapters = $novel->chapters()
            ->orderBy('chapter_number')
            ->get()
            ->map(fn ($chapter): array => [
                'id' => $chapter->id,
                'chapter_number' => $chapter->chapter_number,
                'title' => $chapter->title,
                'status' => $chapter->status->value,
                'published_at' => $chapter->published_at?->toIso8601String(),
            ]);

        return Inertia::render('Studio/Novels/Show', [
            'novel' => [
                'id' => $novel->id,
                'title' => $novel->title,
                'slug' => $novel->slug,
                'cover_path' => $novel->cover_path,
                'synopsis' => $novel->synopsis,
                'status' => $novel->status->value,
                'story_status' => $novel->story_status->value,
                'genres' => $novel->genres->pluck('name'),
                'submissions' => $novel->initialSubmissions()
                    ->latest('submitted_at')
                    ->get()
                    ->map(fn ($submission): array => [
                        'id' => $submission->id,
                        'status' => $submission->status->value,
                        'review_notes' => $submission->review_notes,
                        'submitted_at' => $submission->submitted_at?->toIso8601String(),
                        'decided_at' => $submission->decided_at?->toIso8601String(),
                    ]),
            ],
            'chapters' => $chapters,
        ]);
    }

    public function submitReview(SubmitReviewRequest $request, Novel $novel): RedirectResponse
    {
        Gate::authorize('submitForReview', $novel);

        try {
            app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
                novelId: $novel->id,
                authorId: $request->user()->id,
                chapterIds: array_map('intval', $request->input('chapter_ids', [])),
            ));
        } catch (DomainException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }

        return Redirect::back()->with('success', 'Pengajuan awal berhasil dikirim.');
    }
}
