<?php

namespace App\Http\Controllers;

use App\Application\Reader\Commands\FollowNovel\FollowNovelCommand;
use App\Application\Reader\Commands\FollowNovel\FollowNovelHandler;
use App\Application\Reader\Commands\SaveNovel\SaveNovelCommand;
use App\Application\Reader\Commands\SaveNovel\SaveNovelHandler;
use App\Application\Reader\Commands\UnfollowNovel\UnfollowNovelCommand;
use App\Application\Reader\Commands\UnfollowNovel\UnfollowNovelHandler;
use App\Application\Reader\Commands\UnsaveNovel\UnsaveNovelCommand;
use App\Application\Reader\Commands\UnsaveNovel\UnsaveNovelHandler;
use App\Domain\Shared\Exceptions\DomainException;
use App\Models\Novel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NovelInteractionController extends Controller
{
    public function follow(Request $request, string $novel): RedirectResponse
    {
        $model = $this->findNovel($novel);

        try {
            app(FollowNovelHandler::class)->handle(new FollowNovelCommand(
                userId: $request->user()->id,
                novelId: $model->id,
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Novel diikuti.');
    }

    public function unfollow(Request $request, string $novel): RedirectResponse
    {
        $model = $this->findNovel($novel);

        try {
            app(UnfollowNovelHandler::class)->handle(new UnfollowNovelCommand(
                userId: $request->user()->id,
                novelId: $model->id,
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Novel tidak lagi diikuti.');
    }

    public function save(Request $request, string $novel): RedirectResponse
    {
        $model = $this->findNovel($novel);

        try {
            app(SaveNovelHandler::class)->handle(new SaveNovelCommand(
                userId: $request->user()->id,
                novelId: $model->id,
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Novel disimpan.');
    }

    public function unsave(Request $request, string $novel): RedirectResponse
    {
        $model = $this->findNovel($novel);

        try {
            app(UnsaveNovelHandler::class)->handle(new UnsaveNovelCommand(
                userId: $request->user()->id,
                novelId: $model->id,
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Novel dihapus dari Rak.');
    }

    private function findNovel(string $identifier): Novel
    {
        return Novel::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }
}
