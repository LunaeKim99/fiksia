<?php

namespace App\Domain\Reader\Contracts;

use App\Domain\Reader\Entities\ReadingProgressEntity;

interface LibraryRepository
{
    public function follow(int $userId, int $novelId): void;

    public function unfollow(int $userId, int $novelId): void;

    public function isFollowing(int $userId, int $novelId): bool;

    public function saveNovel(int $userId, int $novelId): void;

    public function unsaveNovel(int $userId, int $novelId): void;

    public function isSaved(int $userId, int $novelId): bool;

    public function upsertProgress(int $userId, int $novelId, int $chapterId, ?int $position, int $lastReadAtTimestamp): void;

    public function findProgress(int $userId, int $novelId): ?ReadingProgressEntity;

    public function addBookmark(int $userId, int $chapterId, ?int $position, ?string $note): void;

    public function removeBookmark(int $userId, int $chapterId): void;

    public function isBookmarked(int $userId, int $chapterId): bool;
}
