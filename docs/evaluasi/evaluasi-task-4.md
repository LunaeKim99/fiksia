# Evaluasi Task 4 — Application Layer

> Dibuat otomatis setelah Task 4 selesai. Untuk handoff sesi: baca file ini (tidak perlu baca ulang kode lengkap) lalu lanjutkan ke Task 5.

## Ringkasan

Use case inti alur penulis: CreateNovel, CreateDraftChapter, SubmitInitialReview, ModerateInitialSubmission, PublishChapter. Contract repository + entity domain (bukan Eloquent), repository Eloquent, DTO command, transisi status via enum `canTransitionTo`, DI binding, unit + feature test alur persetujuan penuh.

## Status Akhir

| Gate                                    | Hasil                                                                       |
| --------------------------------------- | --------------------------------------------------------------------------- |
| `composer test` (pint + phpstan + pest) | ✅ pint pass, phpstan 0 error, pest 28/28 pass (119 assertions)             |
| `npm run types:check`                   | ✅ pass                                                                     |
| `npm run check`                         | ✅ 34 file format, 23 file lint clean                                       |
| Spec review                             | ✅ Spec compliant                                                           |
| Code quality review                     | ✅ Approved → perubahan diminta (3 Critical + 9 Minor) → semua sudah di-fix |

## File Dibuat

Domain (`app/Domain/`):

- `Novel/Entities/NovelEntity.php`, `Chapter/Entities/ChapterEntity.php`, `Moderation/Entities/InitialSubmissionEntity.php` — readonly, `DateTimeImmutable`
- `Novel/Contracts/NovelRepository.php` — findOrFail, create, update, syncGenres, addTags
- `Chapter/Contracts/ChapterRepository.php` + addTags — findOrFail, create, update (`ponytail:` dipakai Task 5), nextChapterNumber, setPublished
- `Moderation/Contracts/InitialSubmissionRepository.php` — createForNovel, findOrFail, updateDecision, findChapterIds
- `Shared/Contracts/UserRepository.php` — exists
- `Shared/Exceptions/` — `DomainException` base, `NotFoundException`, `UnauthorizedActionException`, `InvalidStatusTransitionException`, `InvalidReviewDecisionException`
- enum `canTransitionTo` (string-keyed) di NovelStatus, ChapterStatus, SubmissionStatus

Infrastructure (`app/Infrastructure/Persistence/Repositories/`):

- `EloquentNovelRepository.php`, `EloquentChapterRepository.php`, `EloquentInitialSubmissionRepository.php`, `EloquentUserRepository.php` — `loadModelOrFail` (catch `ModelNotFoundException` → throw `NotFoundException`), map ke entity

Application (`app/Application/`, handler + command DTO):

- `Novel/Commands/CreateNovel/` — `DB::transaction` (create + syncGenres + addTags)
- `Novel/Commands/PublishChapter/` — ownership + novel Published + chapter canTransition
- `Chapter/Commands/CreateDraftChapter/` — ownership + `nextChapterNumber` + status DRAFT
- `Moderation/Commands/SubmitInitialReview/` — validasi chapterIds non-empty, build items di dalam `DB::transaction`, snapshot cover/synopsis/metadata/chapter, flip novel → InitialReview
- `Moderation/Commands/ModerateInitialSubmission/` — self-moderation guard (`ponytail:` role penuh = Task 6 Policy), clock tunggal `$now = time()`, APPROVED→publish novel+chapter+published_at, REVISION_REQUESTED→revision_required, REJECTED→rejected, notes wajib saat tidak APPROVED; `default => throw`
- `app/Providers/AppServiceProvider.php` — 4 binding contract → impl

Tests:

- `tests/Unit/NovelStatusTransitionTest.php`, `ChapterStatusTransitionTest.php`, `SubmissionStatusTransitionTest.php`
- `tests/Feature/InitialReviewFlowTest.php` — flow penuh: novel→draft→submit→approve→publish→publish bab 3 tanpa acc; reject; revisi+resubmit; non-owner blocked (draft + publish); self-moderation blocked; submit saat published throws; double decision throws; empty chapterIds throws

## File Diubah

- `app/Domain/Novel/Enums/NovelStatus.php`, `Chapter/Enums/ChapterStatus.php`, `Moderation/Enums/SubmissionStatus.php` (canTransitionTo + `ponytail:` Reviewing)
- `app/Providers/AppServiceProvider.php` (register bindings)
- `tests/Pest.php` — registrasi Unit, hapus stub `toBeOne`/`something()`
- `app/Domain/Chapter/Contracts/ChapterRepository.php` (berubah via bound impl)

## Keputusan Penting

- Contract return entity domain (bukan Eloquent model) — Domain tetap murni; `map()` nerjemah Eloquent cast → entity + `DateTimeImmutable`.
- Transisi status di method enum `canTransitionTo` (single source, pure, unit-testable), dipanggil di handler sebelum flip status.
- `DB::transaction` di handler (orchestration); repository single-write.
- `time()` tunggal per moderate action — semua timestamp konsisten, tanpa Carbon masuk domain/repo.
- Own ownership check di application (userId dibanding server-side), Policy penuh = Task 6.
- `ChapterRepository::update()` belum dipakai Task 4 — dijaga (`ponytail:`) untuk edit bab Task 5.

## Kualitas Terverifikasi

- `grep Illuminate app/Domain` → kosong (domain murni).
- Handler pakai constructor injection (repo via interface), tanpa `app()`.
- Test negatif: non-owner, self-moderation, double decision, submit-after-published, empty chapters — semua throw exception domain.
- phpstan 0 error; pint pass; tanpa komentar (kecuali docblock + `ponytail:`).

## Risiko Sisa

- `nextChapterNumber` max+1 race konkuren (`ponytail:` — butuh SELECT FOR UPDATE/retry di MySQL prod).
- Belum ada Policy (Task 6) — role moderator belum dicek di application (hanya anti-self-moderation).
- Belum ada controller/route/UI (Task 5) — use case dipanggil langsung di test.
- Belum ada `NotFound` catch di `EloquentUserRepository::exists` — tidak perlu (boolean).

## Lanjut Ke Task 5

Task 5 membutuhkan (Presentation): controller tipis + route + Inertia page untuk Studio Writer (dashboard + editor draft), Moderator Queue, Novel detail, Reader, Explore search, Library (follow/bookmark/progres). Plus `resources/js/actions/` dan `resources/js/wayfinder/` regenerasi. Penting: konsumsi `ChapterRepository::update()` untuk edit draft bab.
