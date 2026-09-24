<?php

use App\Domain\Chapter\Enums\ChapterStatus;

it('allows valid chapter status transitions', function () {
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Draft, ChapterStatus::Published))->toBeTrue();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Draft, ChapterStatus::Archived))->toBeTrue();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Published, ChapterStatus::Hidden))->toBeTrue();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Published, ChapterStatus::Archived))->toBeTrue();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Hidden, ChapterStatus::Published))->toBeTrue();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Hidden, ChapterStatus::Archived))->toBeTrue();
});

it('blocks invalid chapter status transitions', function () {
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Draft, ChapterStatus::Hidden))->toBeFalse();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Published, ChapterStatus::Draft))->toBeFalse();
    expect(ChapterStatus::canTransitionTo(ChapterStatus::Archived, ChapterStatus::Published))->toBeFalse();
});
