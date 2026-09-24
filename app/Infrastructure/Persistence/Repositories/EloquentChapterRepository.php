<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Entities\ChapterEntity;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class EloquentChapterRepository implements ChapterRepository
{
    public function findOrFail(int $id): ChapterEntity
    {
        return $this->map($this->loadModelOrFail($id));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $novelId, array $data): ChapterEntity
    {
        $dataWithNovel = array_merge($data, ['novel_id' => $novelId]);
        /** @var Chapter $model */
        $model = Chapter::query()->create($dataWithNovel);

        return $this->map($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    // ponytail: update() unused by Task 4 handlers; consumed by Task 5 chapter edit
    public function update(int $id, array $data): ChapterEntity
    {
        $model = $this->loadModelOrFail($id);
        $model->update($data);

        return $this->map($model->fresh() ?? $model);
    }

    public function nextChapterNumber(int $novelId): int
    {
        $max = Chapter::query()->where('novel_id', $novelId)->max('chapter_number');

        // ponytail: max+1 races under concurrent writes; caller wraps in transaction but SQLite lacks SELECT FOR UPDATE — add advisory lock or retry-on-unique-violation for MySQL prod

        return (int) $max + 1;
    }

    public function setPublished(int $id, ?int $publishedAtTimestamp): void
    {
        $model = $this->loadModelOrFail($id);

        $model->status = ChapterStatus::Published;
        $model->published_at = $publishedAtTimestamp !== null ? Carbon::createFromTimestamp($publishedAtTimestamp) : null;
        $model->saveQuietly();
    }

    private function loadModelOrFail(int $id): Chapter
    {
        try {
            /** @var Chapter $model */
            $model = Chapter::query()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundException('Bab tidak ditemukan.', previous: $e);
        }

        return $model;
    }

    private function map(Chapter $chapter): ChapterEntity
    {
        return new ChapterEntity(
            id: (int) $chapter->id,
            novelId: (int) $chapter->novel_id,
            chapterNumber: (int) $chapter->chapter_number,
            title: (string) $chapter->title,
            content: $chapter->content,
            status: $chapter->status,
            isFree: (bool) $chapter->is_free,
            publishedAt: $chapter->published_at?->toDateTimeImmutable(),
        );
    }
}
