<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Reader\Contracts\LibraryRepository;
use App\Domain\Reader\Entities\ReadingProgressEntity;
use App\Models\Bookmark;
use App\Models\Follow;
use App\Models\ReadingProgress;
use App\Models\SavedNovel;
use Illuminate\Support\Carbon;

class EloquentLibraryRepository implements LibraryRepository
{
    public function follow(int $userId, int $novelId): void
    {
        Follow::query()->firstOrCreate([
            'user_id' => $userId,
            'novel_id' => $novelId,
        ]);
    }

    public function unfollow(int $userId, int $novelId): void
    {
        Follow::query()
            ->where('user_id', $userId)
            ->where('novel_id', $novelId)
            ->delete();
    }

    public function isFollowing(int $userId, int $novelId): bool
    {
        return Follow::query()
            ->where('user_id', $userId)
            ->where('novel_id', $novelId)
            ->exists();
    }

    public function saveNovel(int $userId, int $novelId): void
    {
        SavedNovel::query()->firstOrCreate([
            'user_id' => $userId,
            'novel_id' => $novelId,
        ]);
    }

    public function unsaveNovel(int $userId, int $novelId): void
    {
        SavedNovel::query()
            ->where('user_id', $userId)
            ->where('novel_id', $novelId)
            ->delete();
    }

    public function isSaved(int $userId, int $novelId): bool
    {
        return SavedNovel::query()
            ->where('user_id', $userId)
            ->where('novel_id', $novelId)
            ->exists();
    }

    public function upsertProgress(int $userId, int $novelId, int $chapterId, ?int $position, int $lastReadAtTimestamp): void
    {
        ReadingProgress::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'novel_id' => $novelId,
            ],
            [
                'chapter_id' => $chapterId,
                'position' => $position,
                'last_read_at' => Carbon::createFromTimestamp($lastReadAtTimestamp),
            ],
        );
    }

    public function findProgress(int $userId, int $novelId): ?ReadingProgressEntity
    {
        $progress = ReadingProgress::query()
            ->where('user_id', $userId)
            ->where('novel_id', $novelId)
            ->first();

        return $progress instanceof ReadingProgress ? $this->mapProgress($progress) : null;
    }

    public function addBookmark(int $userId, int $chapterId, ?int $position, ?string $note): void
    {
        Bookmark::query()->create([
            'user_id' => $userId,
            'chapter_id' => $chapterId,
            'position' => $position,
            'note' => $note,
        ]);
    }

    public function removeBookmark(int $userId, int $chapterId): void
    {
        Bookmark::query()
            ->where('user_id', $userId)
            ->where('chapter_id', $chapterId)
            ->delete();
    }

    public function isBookmarked(int $userId, int $chapterId): bool
    {
        return Bookmark::query()
            ->where('user_id', $userId)
            ->where('chapter_id', $chapterId)
            ->exists();
    }

    private function mapProgress(ReadingProgress $progress): ReadingProgressEntity
    {
        return new ReadingProgressEntity(
            userId: (int) $progress->user_id,
            novelId: (int) $progress->novel_id,
            chapterId: (int) $progress->chapter_id,
            position: $progress->position === null ? null : (int) $progress->position,
            lastReadAt: $progress->last_read_at?->toDateTimeImmutable(),
        );
    }
}
