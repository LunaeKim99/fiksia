<?php

use App\Domain\Moderation\Enums\SubmissionStatus;

it('allows valid submission status transitions', function () {
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Submitted, SubmissionStatus::Reviewing))->toBeTrue();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Submitted, SubmissionStatus::Approved))->toBeTrue();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Submitted, SubmissionStatus::RevisionRequired))->toBeTrue();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Submitted, SubmissionStatus::Rejected))->toBeTrue();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Reviewing, SubmissionStatus::Approved))->toBeTrue();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Reviewing, SubmissionStatus::RevisionRequired))->toBeTrue();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Reviewing, SubmissionStatus::Rejected))->toBeTrue();
});

it('blocks invalid submission status transitions', function () {
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Approved, SubmissionStatus::Submitted))->toBeFalse();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::Rejected, SubmissionStatus::Approved))->toBeFalse();
    expect(SubmissionStatus::canTransitionTo(SubmissionStatus::RevisionRequired, SubmissionStatus::Reviewing))->toBeFalse();
});
