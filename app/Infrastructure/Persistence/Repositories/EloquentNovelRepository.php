<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Entities\NovelEntity;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Models\Novel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentNovelRepository implements NovelRepository
{
    public function findOrFail(int $id): NovelEntity
    {
        return $this->map($this->loadModelOrFail($id));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NovelEntity
    {
        /** @var Novel $model */
        $model = Novel::query()->create($data);

        return $this->map($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): NovelEntity
    {
        $model = $this->loadModelOrFail($id);
        $model->update($data);

        return $this->map($model->fresh() ?? $model);
    }

    /**
     * @param  array<int>  $tagIds
     */
    public function addTags(int $novelId, array $tagIds): void
    {
        $this->loadModelOrFail($novelId)->tags()->syncWithoutDetaching($tagIds);
    }

    /**
     * @param  array<int>  $genreIds
     */
    public function syncGenres(int $novelId, array $genreIds): void
    {
        $this->loadModelOrFail($novelId)->genres()->sync($genreIds);
    }

    private function loadModelOrFail(int $id): Novel
    {
        try {
            /** @var Novel $model */
            $model = Novel::query()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundException('Novel tidak ditemukan.', previous: $e);
        }

        return $model;
    }

    private function map(Novel $novel): NovelEntity
    {
        return new NovelEntity(
            id: (int) $novel->id,
            userId: (int) $novel->user_id,
            title: (string) $novel->title,
            slug: (string) $novel->slug,
            coverPath: $novel->cover_path,
            synopsis: $novel->synopsis,
            status: $novel->status,
            storyStatus: $novel->story_status,
            publishedAt: $novel->published_at?->toDateTimeImmutable(),
            createdAt: $novel->created_at?->toDateTimeImmutable(),
        );
    }
}
