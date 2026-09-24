<?php

namespace App\Http\Controllers;

use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Novel\Enums\NovelStatus;
use App\Models\Genre;
use App\Models\Novel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $genre = trim((string) $request->query('genre', ''));
        $status = trim((string) $request->query('status', ''));
        $sort = in_array($request->query('sort'), ['latest', 'popular'], true)
            ? (string) $request->query('sort')
            : 'latest';

        $query = Novel::query()
            ->where('status', NovelStatus::Published->value)
            ->with(['user.profile', 'genres'])
            ->withCount(['chapters as chapter_count' => fn ($chapterQuery) => $chapterQuery
                ->where('status', ChapterStatus::Published->value),
            ]);

        if ($q !== '') {
            $query->where(function (Builder $builder) use ($q): void {
                $like = '%'.$q.'%';
                $builder
                    ->where('title', 'like', $like)
                    ->orWhere('synopsis', 'like', $like)
                    ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                        $userQuery
                            ->where('name', 'like', $like)
                            ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery->where('username', 'like', $like));
                    });
            });
        }

        if ($genre !== '') {
            $query->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->where('slug', $genre));
        }

        if (in_array($status, ['ongoing', 'completed', 'hiatus'], true)) {
            $query->where('story_status', $status);
        }

        if ($sort === 'popular') {
            $query->orderByDesc('chapter_count')->orderByDesc('published_at');
        } else {
            $query->orderByDesc('published_at')->orderByDesc('id');
        }

        $paginator = $query->paginate(12)->withQueryString();
        $novels = $paginator->getCollection()->map(fn (Novel $novel): array => $this->serialize($novel));

        return Inertia::render('Explore', [
            'novels' => [
                'data' => $novels,
                'links' => $paginator->linkCollection()->values()->all(),
            ],
            'genres' => Genre::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Genre $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                ])
                ->values(),
            'activeFilters' => [
                'q' => $q,
                'genre' => $genre,
                'status' => $status,
                'sort' => $sort,
            ],
            'totalResults' => $paginator->total(),
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
            'chapterCount' => $novel->getAttribute('chapter_count'),
            'genres' => $novel->genres->map(fn ($genre): array => [
                'id' => $genre->id,
                'name' => $genre->name,
                'slug' => $genre->slug,
            ])->values(),
        ];
    }
}
