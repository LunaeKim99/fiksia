<?php

namespace App\Domain\Novel\Contracts;

use App\Domain\Novel\Entities\NovelEntity;

interface NovelRepository
{
    public function findOrFail(int $id): NovelEntity;

    /**
     * Read a novel inside a transaction with an exclusive row lock.
     *
     * The lock is enforced by MySQL/InnoDB in production. SQLite (tests) compiles
     * `lockForUpdate()` away, so the mutual exclusion itself is not test-covered;
     * do not remove the lock or this method as "unused".
     */
    public function findOrFailForUpdate(int $id): NovelEntity;

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
