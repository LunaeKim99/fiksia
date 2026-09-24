<?php

use App\Domain\Novel\Enums\NovelStatus;

it('allows valid status transitions', function () {
    expect(NovelStatus::canTransitionTo(NovelStatus::Draft, NovelStatus::InitialReview))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::InitialReview, NovelStatus::Published))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::InitialReview, NovelStatus::RevisionRequired))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::InitialReview, NovelStatus::Rejected))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::RevisionRequired, NovelStatus::InitialReview))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::RevisionRequired, NovelStatus::Suspended))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::RevisionRequired, NovelStatus::Archived))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::Published, NovelStatus::Suspended))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::Published, NovelStatus::Archived))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::Suspended, NovelStatus::Published))->toBeTrue();
    expect(NovelStatus::canTransitionTo(NovelStatus::Suspended, NovelStatus::Archived))->toBeTrue();
});

it('blocks invalid status transitions', function () {
    expect(NovelStatus::canTransitionTo(NovelStatus::Draft, NovelStatus::Published))->toBeFalse();
    expect(NovelStatus::canTransitionTo(NovelStatus::Draft, NovelStatus::Rejected))->toBeFalse();
    expect(NovelStatus::canTransitionTo(NovelStatus::Published, NovelStatus::Draft))->toBeFalse();
    expect(NovelStatus::canTransitionTo(NovelStatus::Published, NovelStatus::InitialReview))->toBeFalse();
    expect(NovelStatus::canTransitionTo(NovelStatus::Rejected, NovelStatus::Draft))->toBeFalse();
    expect(NovelStatus::canTransitionTo(NovelStatus::Archived, NovelStatus::Published))->toBeFalse();
});
