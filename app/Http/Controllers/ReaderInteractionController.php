<?php

namespace App\Http\Controllers;

use App\Application\Reader\Commands\AddBookmark\AddBookmarkCommand;
use App\Application\Reader\Commands\AddBookmark\AddBookmarkHandler;
use App\Application\Reader\Commands\RemoveBookmark\RemoveBookmarkCommand;
use App\Application\Reader\Commands\RemoveBookmark\RemoveBookmarkHandler;
use App\Application\Reader\Commands\SaveReadingProgress\SaveReadingProgressCommand;
use App\Application\Reader\Commands\SaveReadingProgress\SaveReadingProgressHandler;
use App\Domain\Shared\Exceptions\DomainException;
use App\Http\Requests\Reader\BookmarkRequest;
use App\Http\Requests\Reader\RemoveBookmarkRequest;
use App\Http\Requests\Reader\SaveProgressRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ReaderInteractionController extends Controller
{
    public function progress(SaveProgressRequest $request): RedirectResponse|Response
    {
        try {
            app(SaveReadingProgressHandler::class)->handle(new SaveReadingProgressCommand(
                userId: $request->user()->id,
                novelId: $request->integer('novel_id'),
                chapterId: $request->integer('chapter_id'),
                position: $request->input('position') === null ? null : $request->integer('position'),
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->noContent();
    }

    public function bookmark(BookmarkRequest $request): RedirectResponse
    {
        try {
            app(AddBookmarkHandler::class)->handle(new AddBookmarkCommand(
                userId: $request->user()->id,
                chapterId: $request->integer('chapter_id'),
                position: $request->input('position') === null ? null : $request->integer('position'),
                note: null,
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bab di-bookmark.');
    }

    public function unbookmark(RemoveBookmarkRequest $request): RedirectResponse
    {
        try {
            app(RemoveBookmarkHandler::class)->handle(new RemoveBookmarkCommand(
                userId: $request->user()->id,
                chapterId: $request->integer('chapter_id'),
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bookmark dihapus.');
    }
}
