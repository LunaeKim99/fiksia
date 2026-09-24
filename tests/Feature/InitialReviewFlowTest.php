<?php

use App\Application\Chapter\Commands\CreateDraftChapter\CreateDraftChapterCommand;
use App\Application\Chapter\Commands\CreateDraftChapter\CreateDraftChapterHandler;
use App\Application\Moderation\Commands\ModerateInitialSubmission\ModerateInitialSubmissionCommand;
use App\Application\Moderation\Commands\ModerateInitialSubmission\ModerateInitialSubmissionHandler;
use App\Application\Moderation\Commands\SubmitInitialReview\SubmitInitialReviewCommand;
use App\Application\Moderation\Commands\SubmitInitialReview\SubmitInitialReviewHandler;
use App\Application\Novel\Commands\CreateNovel\CreateNovelCommand;
use App\Application\Novel\Commands\CreateNovel\CreateNovelHandler;
use App\Application\Novel\Commands\PublishChapter\PublishChapterCommand;
use App\Application\Novel\Commands\PublishChapter\PublishChapterHandler;
use App\Domain\Chapter\Contracts\ChapterRepository;
use App\Domain\Chapter\Enums\ChapterStatus;
use App\Domain\Moderation\Contracts\InitialSubmissionRepository;
use App\Domain\Moderation\Enums\SubmissionStatus;
use App\Domain\Novel\Contracts\NovelRepository;
use App\Domain\Novel\Enums\NovelStatus;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\Exceptions\InvalidReviewDecisionException;
use App\Domain\Shared\Exceptions\InvalidStatusTransitionException;
use App\Domain\Shared\Exceptions\UnauthorizedActionException;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\InitialSubmission;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('reads a submission with a row lock inside a transaction', function () {
    $submission = InitialSubmission::factory()->create();

    $locked = DB::transaction(
        fn () => app(InitialSubmissionRepository::class)->findOrFailForUpdate($submission->id),
    );

    expect($locked->id)->toBe($submission->id)
        ->and($locked->status)->toBe(SubmissionStatus::Submitted);
});

it('runs the full initial review approval and publish flow', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();
    $genre = Genre::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Uji Alur',
        synopsis: 'Sinopsis untuk pengujian.',
        coverPath: 'covers/test.jpg',
        genreIds: [$genre->id],
    ));

    expect($novel->status)->toBe(NovelStatus::Draft);
    expect($novel->slug)->not->toBeEmpty();

    $createChapter = app(CreateDraftChapterHandler::class);

    $chapterOne = $createChapter->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi bab satu.',
    ));

    $chapterTwo = $createChapter->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Dua',
        content: 'Isi bab dua.',
    ));

    expect($chapterOne->chapterNumber)->toBe(1);
    expect($chapterOne->status)->toBe(ChapterStatus::Draft);
    expect($chapterTwo->chapterNumber)->toBe(2);

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapterOne->id, $chapterTwo->id],
    ));

    expect($submission->status)->toBe(SubmissionStatus::Submitted);
    expect($submission->novelId)->toBe($novel->id);
    expect(Novel::query()->findOrFail($novel->id)->status)->toBe(NovelStatus::InitialReview);

    expect(fn () => app(PublishChapterHandler::class)->handle(new PublishChapterCommand(
        chapterId: $chapterOne->id,
        authorId: $author->id,
    )))->toThrow(InvalidStatusTransitionException::class);

    app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Approved,
        notes: null,
    ));

    $approvedNovel = Novel::query()->findOrFail($novel->id);
    expect($approvedNovel->status)->toBe(NovelStatus::Published);
    expect($approvedNovel->published_at)->not->toBeNull();

    $approvedChapterIds = app(InitialSubmissionRepository::class)->findChapterIds($submission->id);
    expect($approvedChapterIds)->toEqualCanonicalizing([$chapterOne->id, $chapterTwo->id]);

    $approvedChapters = Chapter::query()->whereIn('id', $approvedChapterIds)->get();

    expect($approvedChapters)->toHaveCount(2);
    foreach ($approvedChapters as $chapter) {
        expect($chapter->status)->toBe(ChapterStatus::Published);
        expect($chapter->published_at)->not->toBeNull();
    }

    $chapterThree = $createChapter->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Tiga',
        content: 'Isi bab tiga.',
    ));

    expect($chapterThree->chapterNumber)->toBe(3);

    $publishedThree = app(PublishChapterHandler::class)->handle(new PublishChapterCommand(
        chapterId: $chapterThree->id,
        authorId: $author->id,
    ));

    expect($publishedThree->status)->toBe(ChapterStatus::Published);
    expect($publishedThree->chapterNumber)->toBe(3);
    expect($publishedThree->publishedAt)->not->toBeNull();
});

it('rejects a submission when notes are missing', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Tanpa Catatan',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    expect(fn () => app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Rejected,
        notes: null,
    )))->toThrow(InvalidReviewDecisionException::class, 'Catatan review wajib diisi untuk revisi atau penolakan.');
});

it('marks novel as rejected when moderator rejects with notes', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Ditolak',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Rejected,
        notes: 'Konten melanggar pedoman.',
    ));

    $rejectedNovel = Novel::query()->findOrFail($novel->id);
    expect($rejectedNovel->status)->toBe(NovelStatus::Rejected);
    expect($rejectedNovel->published_at)->toBeNull();

    $rejectedSubmission = InitialSubmission::query()->findOrFail($submission->id);
    expect($rejectedSubmission->status)->toBe(SubmissionStatus::Rejected);
    expect($rejectedSubmission->review_notes)->toBe('Konten melanggar pedoman.');
    expect($rejectedSubmission->reviewer_id)->toBe($moderator->id);
    expect($rejectedSubmission->decided_at)->not->toBeNull();

    $chapterModel = Chapter::query()->findOrFail($chapter->id);
    expect($chapterModel->status)->toBe(ChapterStatus::Draft);
    expect($chapterModel->published_at)->toBeNull();
});

it('marks novel as revision required and allows resubmission', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Revisi',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::RevisionRequired,
        notes: 'Perbaiki sinopsis.',
    ));

    expect(Novel::query()->findOrFail($novel->id)->status)->toBe(NovelStatus::RevisionRequired);

    $resubmission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    expect($resubmission->status)->toBe(SubmissionStatus::Submitted);
    expect(Novel::query()->findOrFail($novel->id)->status)->toBe(NovelStatus::InitialReview);
});

it('prevents non-owner from creating a draft chapter', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $owner->id,
        title: 'Novel Milik Orang Lain',
    ));

    expect(fn () => app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $intruder->id,
        title: 'Bab Ilegal',
        content: 'Isi.',
    )))->toThrow(UnauthorizedActionException::class);
});

it('increments chapter number per novel', function () {
    $author = User::factory()->create();
    $novelRepository = app(NovelRepository::class);
    $chapterRepository = app(ChapterRepository::class);

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Urutan',
    ));

    expect($chapterRepository->nextChapterNumber($novel->id))->toBe(1);

    $createChapter = app(CreateDraftChapterHandler::class);
    $createChapter->handle(new CreateDraftChapterCommand($novel->id, $author->id, 'Satu', 'Isi.'));
    expect($chapterRepository->nextChapterNumber($novel->id))->toBe(2);

    $createChapter->handle(new CreateDraftChapterCommand($novel->id, $author->id, 'Dua', 'Isi.'));
    expect($chapterRepository->nextChapterNumber($novel->id))->toBe(3);

    $other = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Lain',
    ));

    expect($chapterRepository->nextChapterNumber($other->id))->toBe(1);
    expect($novelRepository->findOrFail($novel->id)->id)->toBe($novel->id);
});

it('rejects a second decision on the same submission', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Keputusan Kedua',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Approved,
        notes: null,
    ));

    expect(fn () => app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Rejected,
        notes: 'Berubah pikiran.',
    )))->toThrow(InvalidReviewDecisionException::class, 'Submission ini sudah memiliki keputusan.');
});

it('rejects submitting an already published novel', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Sudah Terbit',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $moderator->id,
        decision: SubmissionStatus::Approved,
        notes: null,
    ));

    expect(Novel::query()->findOrFail($novel->id)->status)->toBe(NovelStatus::Published);

    expect(fn () => app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    )))->toThrow(InvalidStatusTransitionException::class);
});

it('prevents non-owner from publishing someone elses chapter', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $owner->id,
        title: 'Novel Publish Orang Lain',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $owner->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    expect(fn () => app(PublishChapterHandler::class)->handle(new PublishChapterCommand(
        chapterId: $chapter->id,
        authorId: $intruder->id,
    )))->toThrow(UnauthorizedActionException::class);
});

it('prevents moderator from moderating their own submission', function () {
    $author = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Self Moderation',
    ));

    $chapter = app(CreateDraftChapterHandler::class)->handle(new CreateDraftChapterCommand(
        novelId: $novel->id,
        authorId: $author->id,
        title: 'Bab Satu',
        content: 'Isi.',
    ));

    $submission = app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [$chapter->id],
    ));

    expect(fn () => app(ModerateInitialSubmissionHandler::class)->handle(new ModerateInitialSubmissionCommand(
        submissionId: $submission->id,
        moderatorId: $author->id,
        decision: SubmissionStatus::Approved,
        notes: null,
    )))->toThrow(UnauthorizedActionException::class, 'Moderator tidak boleh memoderasi karya sendiri.');
});

it('rejects submitting without any chapters', function () {
    $author = User::factory()->create();

    $novel = app(CreateNovelHandler::class)->handle(new CreateNovelCommand(
        userId: $author->id,
        title: 'Novel Tanpa Bab',
    ));

    expect(fn () => app(SubmitInitialReviewHandler::class)->handle(new SubmitInitialReviewCommand(
        novelId: $novel->id,
        authorId: $author->id,
        chapterIds: [],
    )))->toThrow(DomainException::class, 'Pengajuan awal wajib menyertakan minimal satu bab.');
});
