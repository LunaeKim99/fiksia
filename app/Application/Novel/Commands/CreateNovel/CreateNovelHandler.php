<?php

namespace App\Application\Novel\Commands\CreateNovel;

use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Entities\NovelEntity;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Contracts\UserRepository;
use App\Domain\Shared\Exceptions\NotFoundException;
use Illuminate\Support\Facades\DB;

class CreateNovelHandler
{
    public function __construct(
        private NovelRepository $novelRepository,
        private UserRepository $userRepository,
    ) {}

    public function handle(CreateNovelCommand $command): NovelEntity
    {
        if (! $this->userRepository->exists($command->userId)) {
            throw new NotFoundException('Penulis tidak ditemukan.');
        }

        return DB::transaction(function () use ($command): NovelEntity {
            $novel = $this->novelRepository->create([
                'user_id' => $command->userId,
                'title' => $command->title,
                'synopsis' => $command->synopsis,
                'cover_path' => $command->coverPath,
                'story_status' => $command->storyStatus,
                'status' => NovelStatus::Draft,
            ]);

            if ($command->genreIds !== []) {
                $this->novelRepository->syncGenres($novel->id, $command->genreIds);
            }

            if ($command->tagIds !== []) {
                $this->novelRepository->addTags($novel->id, $command->tagIds);
            }

            return $this->novelRepository->findOrFail($novel->id);
        });
    }
}
