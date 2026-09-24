# Evaluasi Task 5c — Library + Moderator Queue

> Dibuat otomatis setelah Task 5c selesai. Untuk handoff sesi: baca file ini lalu lanjutkan ke Task 6.

## Ringkasan

Library pribadi (4 tab: mengikuti, disimpan, sedang dibaca, selesai + pagination), follow/save/unfollow/unsave di detail novel, bookmark + reading progress di reader, "Lanjut Membaca" di Home, dan antrean moderator untuk review pengajuan awal. Domain Library (tabel + use case) dibangun lebih dulu sebagai 5c-1, lalu presentation-nya 5c-2.

## Status Akhir

| Gate                                    | Hasil                                                              |
| --------------------------------------- | ------------------------------------------------------------------ |
| `composer test` (pint + phpstan + pest) | ✅ pint pass, phpstan 0, pest 101/101 (626 assertions)             |
| `npm run types:check`                   | ✅ tsc --noEmit clean                                              |
| `npm run check`                         | ✅ 55 file format, 35 file lint clean                              |
| `php artisan route:list`                | ✅ 38 route                                                        |
| `php artisan migrate:fresh --seed`      | ✅ 20 migration, 8 genre                                           |
| Spec review 5c-1                        | ✅ setelah 1 Important (bookmark novel-published guard) diperbaiki |
| Spec review 5c-2                        | ✅ setelah 1 Critical (moderation race) + 3 Important diperbaiki   |

## File Dibuat (5c-1 — domain)

Enums/models/migrations:

- `app/Domain/Shared/Enums/UserRole.php` — member/moderator/admin
- `database/migrations/2026_09_25_000001_add_role_to_users_table.php` — `role` default 'member'
- `database/migrations/2026_09_25_000002_create_follows_table.php` — unique (user_id, novel_id), index novel_id
- `database/migrations/2026_09_25_000003_create_saved_novels_table.php` — unique pair
- `database/migrations/2026_09_25_000004_create_reading_progress_table.php` — unique (user_id, novel_id), position, last_read_at
- `database/migrations/2026_09_25_000005_create_bookmarks_table.php` — user_id, chapter_id, position, note
- `app/Models/Follow.php`, `SavedNovel.php`, `ReadingProgress.php`, `Bookmark.php`

Domain contract + entity:

- `app/Domain/Reader/Contracts/LibraryRepository.php`
- `app/Domain/Reader/Entities/ReadingProgressEntity.php`

Use cases (7, masing-masing Command + Handler):

- `app/Application/Reader/Commands/FollowNovel/` — published check, idempotent
- `UnfollowNovel/`, `SaveNovel/`, `UnsaveNovel/` — idempotent
- `SaveReadingProgress/` — novel+chapter published, cross-novel check, position >=0, server `time()`
- `AddBookmark/` — chapter published **dan** novel published (fix review), position >=0
- `RemoveBookmark/`

Infrastructure:

- `app/Infrastructure/Persistence/Repositories/EloquentLibraryRepository.php` — firstOrCreate/updateOrCreate idempotent
- 4 factory: `FollowFactory`, `SavedNovelFactory`, `ReadingProgressFactory`, `BookmarkFactory`
- `tests/Feature/Library/LibraryTest.php` (idempotency, unpublished rejection, cross-novel, negative position, no-write assertions), `tests/Unit/UserRoleTest.php`

## File Dibuat (5c-2 — presentation)

Controllers + requests:

- `app/Http/Controllers/LibraryController.php` — 4 tab query + counts + pagination 12
- `app/Http/Controllers/NovelInteractionController.php` — follow/unfollow/save/unsave
- `app/Http/Controllers/ReaderInteractionController.php` — bookmark/unbookmark/progress (progress `204 No Content`)
- `app/Http/Controllers/Moderation/ModerationQueueController.php` — queue + submission workspace + decide
- `app/Http/Requests/Reader/SaveProgressRequest.php`, `BookmarkRequest.php`, `RemoveBookmarkRequest.php`
- `app/Http/Requests/Moderation/ModerateSubmissionRequest.php` — `authorize()` butuh `isModerator()`

Frontend:

- `resources/js/pages/Library.tsx` — replaced placeholder, 4 tab + pagination
- `resources/js/pages/Moderation/Queue.tsx`, `Submission.tsx` — review workspace + decision form
- `resources/js/pages/Home.tsx` — section "Lanjut Membaca" (logged-in only)
- `resources/js/pages/Novel/Show.tsx` — tombol Ikuti/Simpan real + "Lanjut Baca"
- `resources/js/pages/Reader/Show.tsx` — bookmark toggle + progress save fire-and-forget
- `resources/js/types/library.ts` — `ModerationStatus`/`ModerationItemType` union types

Tests:

- `tests/Feature/Moderation/ModerationQueueTest.php` — 403 member queue/submission/decide, moderator 200, decide approve/revision, invalid decision 422, member invalid 403
- `tests/Feature/Library/LibraryTest.php` — ditambah: unpublished excluded semua tab, pagination 2 halaman + tab preserved, progress 204 + row created

## File Diubah

- `app/Models/User.php` — `role` fillable + cast `UserRole` + `isModerator()`/`isAdmin()`; relasi follows/savedNovels/readingProgress/bookmarks
- `app/Models/Novel.php`, `Chapter.php` — relasi baru
- `database/factories/UserFactory.php` — default role member
- `app/Providers/AppServiceProvider.php` — bind `LibraryRepository`
- `app/Domain/Moderation/Contracts/InitialSubmissionRepository.php` — tambah `findOrFailForUpdate()`
- `app/Infrastructure/Persistence/Repositories/EloquentInitialSubmissionRepository.php` — `lockForUpdate()` (Critical fix)
- `app/Application/Moderation/Commands/ModerateInitialSubmission/ModerateInitialSubmissionHandler.php` — baca submission dengan lock DI DALAM transaction
- `app/Http/Controllers/HomeController.php`, `NovelController.php`, `ReaderController.php` — continueReading, isFollowing/isSaved, isBookmarked
- `routes/web.php` — `/library` auth, 4 interaction route, 3 moderation route
- `resources/js/types/catalog.ts`, `index.ts`

## Keputusan Penting

- **Role**: kolom `role` string + enum internal (BUKAN package permission) — minimum dependency.
- **Idempotensi**: follow/save pakai `firstOrCreate`; unfollow/unsave/removeBookmark delete idempotent; progress `updateOrCreate` (satu baris per user+novel).
- **Bookmark guard**: chapter published **DAN** novel published (fix review — chapter published bisa novel-nya suspended).
- **Unfollow/unsave/unbookmark pakai POST** (bukan DELETE) — CSRF sederhana, tanpa method spoofing.
- **Progress fire-and-forget**: `204` + `onHttpException: () => false` di client agar Inertia tidak menampilkan modal error; reading tidak pernah terblokir.
- **Moderator concurrency**: Option A — `findOrFailForUpdate()` dengan `lockForUpdate()` DI DALAM transaction, `validateDecision` dijalankan terhadap state terkunci. (`ponytail:` SQLite tidak menguji row lock; MySQL prod iya).
- **403 > 422**: `ModerateSubmissionRequest::authorize()` cek moderator, jadi member dengan `decision` invalid dapat 403 bukan 422.
- **Library pagination**: 12 per halaman + `withQueryString()` (tab preserved) — sesuai PRD §9.
- **Nav Moderasi tidak ditambahkan** — moderator akses via URL langsung (Task 6 bisa tambah nav role-aware).
- **position** selalu null di 5c — scroll tracking ditunda.

## Risiko Sisa

- Row lock tidak teruji di SQLite dev — perlu uji di MySQL sebelum prod.
- Progress POST tanpa throttle — MVP-acceptable, tambah throttle saat traffic naik.
- Belum ada scroll position tracking.
- Belum ada Policy (Task 6) — moderator check via `abort_if` di controller.
- Belum ada audit log untuk keputusan moderator (Task 6 — tabel `moderation_actions` sudah ada tapi belum ditulis).
- Belum ada E2E/browser test.

## Lanjut Ke Task 6

Task 6 perlu: Policy classes (server-side authz), audit log write saat moderasi, validasi hardening, HTTP feature test komprehensif, final gate run. Tabel `moderation_actions` sudah ada dari Task 3 — hooking decision handler ke audit log adalah bagian Task 6.
