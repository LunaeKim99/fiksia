<?php

namespace App\Domain\Chapter\Contracts;

use App\Domain\Chapter\Entities\ChapterEntity;

interface ChapterRepository
{
    public function findOrFail(int $id): ChapterEntity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $novelId, array $data): ChapterEntity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): ChapterEntity;

    public function nextChapterNumber(int $novelId): int;

    public function setPublished(int $id, ?int $publishedAtTimestamp): void;
}
