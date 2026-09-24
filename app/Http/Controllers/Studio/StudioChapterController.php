<?php

namespace App\Http\Controllers\Studio;

use App\Application\Chapter\Commands\CreateDraftChapter\CreateDraftChapterCommand;
use App\Application\Chapter\Commands\CreateDraftChapter\CreateDraftChapterHandler;
use App\Application\Chapter\Commands\UpdateDraftChapter\UpdateDraftChapterCommand;
use App\Application\Chapter\Commands\UpdateDraftChapter\UpdateDraftChapterHandler;
use App\Application\Novel\Commands\PublishChapter\PublishChapterCommand;
use App\Application\Novel\Commands\PublishChapter\PublishChapterHandler;
use App\Domain\Shared\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreChapterRequest;
use App\Http\Requests\Studio\UpdateChapterRequest;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class StudioChapterController extends Controller
{
    public function create(Request $request, Novel $novel): Response
    {
        Gate::authorize('create', [Chapter::class, $novel]);

        return Inertia::render('Studio/Chapters/Create', [
            'novel' => [
                'id' => $novel->id,
                'title' => $novel->title,
                'slug' => $novel->slug,
            ],
        ]);
    }

    public function store(StoreChapterRequest $request, Novel $novel): RedirectResponse
    {
        Gate::authorize('create', [Chapter::class, $novel]);

        try {
            $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
                novelId: $novel->id,
                authorId: $request->user()->id,
                title: $request->string('title')->toString(),
                content: $request->input('content'),
            ));
        } catch (DomainException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }

        return Redirect::route('studio.novels.show', ['novel' => $novel->id])
            ->with('success', 'Draft bab berhasil dibuat.');
    }

    public function edit(Request $request, Chapter $chapter): Response
    {
        $chapter->load('novel');
        Gate::authorize('update', $chapter);

        return Inertia::render('Studio/Chapters/Edit', [
            'chapter' => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'content' => $chapter->content,
                'status' => $chapter->status->value,
            ],
            'novel' => [
                'id' => $chapter->novel->id,
                'title' => $chapter->novel->title,
                'slug' => $chapter->novel->slug,
                'status' => $chapter->novel->status->value,
            ],
        ]);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): RedirectResponse
    {
        $chapter->load('novel');
        Gate::authorize('update', $chapter);

        try {
            app(UpdateDraftChapterHandler::class)->handle(new UpdateDraftChapterCommand(
                chapterId: $chapter->id,
                authorId: $request->user()->id,
                title: $request->string('title')->toString(),
                content: $request->input('content'),
            ));
        } catch (DomainException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }

        return Redirect::back()->with('success', 'Draft berhasil disimpan.');
    }

    public function publish(Request $request, Chapter $chapter): RedirectResponse
    {
        $chapter->load('novel');
        Gate::authorize('publish', $chapter);

        try {
            app(PublishChapterHandler::class)->handle(new PublishChapterCommand(
                chapterId: $chapter->id,
                authorId: $request->user()->id,
            ));
        } catch (DomainException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }

        return Redirect::back()->with('success', 'Bab berhasil diterbitkan.');
    }
}
