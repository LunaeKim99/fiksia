<?php

namespace App\Http\Controllers\Moderation;

use App\Application\Moderation\Commands\ModerateInitialSubmission\ModerateInitialSubmissionCommand;
use App\Application\Moderation\Commands\ModerateInitialSubmission\ModerateInitialSubmissionHandler;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Shared\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ModerateSubmissionRequest;
use App\Models\InitialSubmission;
use App\Models\SubmissionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ModerationQueueController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InitialSubmission::class);

        $submissions = InitialSubmission::query()
            ->whereIn('status', [
                SubmissionStatus::Submitted->value,
                SubmissionStatus::Reviewing->value,
            ])
            ->with(['novel:id,title,slug', 'submittedBy:id,name'])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Moderation/Queue', [
            'submissions' => [
                'data' => $submissions->getCollection()
                    ->map(fn (InitialSubmission $submission): array => $this->serializeQueueItem($submission))
                    ->values(),
                'links' => $submissions->linkCollection()->values()->all(),
            ],
        ]);
    }

    public function show(Request $request, InitialSubmission $submission): Response
    {
        Gate::authorize('view', $submission);

        $submission->load([
            'submittedBy:id,name',
            'novel:id,title,slug,synopsis,cover_path',
            'novel.genres:id,name',
            'submissionItems:id,submission_id,item_type,chapter_id,snapshot,sort_order',
            'submissionItems.chapter:id,chapter_number,title',
        ]);

        $items = $submission->submissionItems
            ->sortBy('sort_order')
            ->values()
            ->map(fn (SubmissionItem $item): array => $this->serializeItem($item));

        return Inertia::render('Moderation/Submission', [
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status->value,
                'statusLabel' => $this->statusLabel($submission->status),
                'isDecided' => in_array($submission->status, [
                    SubmissionStatus::Approved,
                    SubmissionStatus::RevisionRequired,
                    SubmissionStatus::Rejected,
                ], true),
                'reviewNotes' => $submission->review_notes,
                'submittedAt' => $submission->submitted_at?->toIso8601String(),
                'decidedAt' => $submission->decided_at?->toIso8601String(),
                'author' => [
                    'id' => $submission->submittedBy->id,
                    'name' => $submission->submittedBy->name,
                ],
                'novel' => [
                    'id' => $submission->novel->id,
                    'title' => $submission->novel->title,
                    'slug' => $submission->novel->slug,
                    'synopsis' => $submission->novel->synopsis,
                    'coverAvailable' => $submission->novel->cover_path !== null,
                    'coverUrl' => $submission->novel->cover_path === null ? null : route('novels.cover', ['novel' => $submission->novel->id]),
                    'genres' => $submission->novel->genres->map(fn ($genre): array => [
                        'id' => $genre->id,
                        'name' => $genre->name,
                    ])->values(),
                ],
                'items' => $items,
            ],
        ]);
    }

    public function decide(ModerateSubmissionRequest $request, InitialSubmission $submission): RedirectResponse
    {
        Gate::authorize('review', $submission);

        try {
            app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
                submissionId: $submission->id,
                moderatorId: $request->user()->id,
                decision: SubmissionStatus::from($request->string('decision')->toString()),
                notes: $request->input('notes'),
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('moderation.queue')->with('success', 'Keputusan review tersimpan.');
    }

    /** @return array<string, mixed> */
    private function serializeQueueItem(InitialSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'novel' => [
                'title' => $submission->novel->title,
                'slug' => $submission->novel->slug,
            ],
            'submittedBy' => [
                'id' => $submission->submittedBy->id,
                'name' => $submission->submittedBy->name,
            ],
            'submittedAt' => $submission->submitted_at?->toIso8601String(),
            'status' => $submission->status->value,
            'statusLabel' => $this->statusLabel($submission->status),
            'reviewNotes' => $submission->review_notes,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeItem(SubmissionItem $item): array
    {
        $snapshot = $item->snapshot ?? [];

        return [
            'id' => $item->id,
            'itemType' => $item->item_type->value,
            'label' => $this->itemLabel($item->item_type->value),
            'chapterId' => $item->chapter_id,
            'chapterNumber' => $this->intValue($snapshot['chapter_number'] ?? $item->chapter?->chapter_number),
            'title' => $this->stringValue($snapshot['title'] ?? $item->chapter?->title),
            'snapshot' => $snapshot,
        ];
    }

    private function statusLabel(SubmissionStatus $status): string
    {
        return match ($status) {
            SubmissionStatus::Submitted => 'Menunggu review',
            SubmissionStatus::Reviewing => 'Sedang direview',
            SubmissionStatus::Approved => 'Disetujui',
            SubmissionStatus::RevisionRequired => 'Perlu revisi',
            SubmissionStatus::Rejected => 'Ditolak',
        };
    }

    private function itemLabel(string $type): string
    {
        return match ($type) {
            'cover' => 'Sampul',
            'synopsis' => 'Sinopsis',
            'metadata' => 'Metadata',
            'chapter' => 'Bab',
            default => 'Item',
        };
    }

    private function intValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
