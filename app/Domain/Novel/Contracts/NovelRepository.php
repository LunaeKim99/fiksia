<?php

namespace App\Domain\Novel\Contracts;

use App\Domain\Novel\Entities\NovelEntity;

interface NovelRepository
{
    public function findOrFail(int $id): NovelEntity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NovelEntity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): NovelEntity;

    /**
     * @param  array<int>  $tagIds
     */
    public function addTags(int $novelId, array $tagIds): void;

    /**
     * @param  array<int>  $genreIds
     */
    public function syncGenres(int $novelId, array $genreIds): void;
}
