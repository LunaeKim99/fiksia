<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Novel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            return Inertia::render('Studio/Index', [
                'novels' => [],
                'isGuest' => true,
            ]);
        }

        $novels = Novel::query()
            ->where('user_id', $user->id)
            ->with(['genres', 'initialSubmissions'])
            ->withCount('chapters as chapter_count')
            ->withCount(['chapters as draft_count' => fn ($query) => $query->where('status', 'draft')])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Novel $novel): array => $this->serialize($novel));

        return Inertia::render('Studio/Index', [
            'novels' => $novels,
            'isGuest' => false,
        ]);
    }

    /** @return array<string, mixed> */
    private function serialize(Novel $novel): array
    {
        $latest = $novel->initialSubmissions()->latest('submitted_at')->first();

        return [
            'id' => $novel->id,
            'title' => $novel->title,
            'slug' => $novel->slug,
            'cover_path' => $novel->cover_path,
            'status' => $novel->status->value,
            'story_status' => $novel->story_status->value,
            'chapter_count' => $novel->getAttribute('chapter_count'),
            'draft_count' => $novel->getAttribute('draft_count'),
            'genres' => $novel->genres->pluck('name'),
            'latest_submission' => $latest === null ? null : [
                'id' => $latest->id,
                'status' => $latest->status->value,
                'review_notes' => $latest->review_notes,
                'submitted_at' => $latest->submitted_at?->toIso8601String(),
                'decided_at' => $latest->decided_at?->toIso8601String(),
            ],
            'updated_at' => $novel->updated_at?->toIso8601String(),
        ];
    }
}
