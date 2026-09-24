# Evaluasi Task 6 — Policies, Audit Log, Rate Limit (FINAL)

> Dibuat otomatis setelah Task 6 selesai. **Fase 0/1 MVP selesai.** Untuk handoff sesi: baca file ini + `README.md`.

## Ringkasan

Server-side authorization via Policy (3 policy class), audit log untuk setiap keputusan moderator (dalam transaction yang sama), rate limit untuk 3 endpoint sensitif, validation hardening, dan test matriks otorisasi komprehensif.

## Status Akhir

| Gate                                    | Hasil                                                        |
| --------------------------------------- | ------------------------------------------------------------ |
| `composer test` (pint + phpstan + pest) | ✅ pint pass, phpstan 0, pest 134/134 (728 assertions)       |
| `npm run types:check`                   | ✅ tsc --noEmit clean                                        |
| `npm run check`                         | ✅ 56 file format, 35 file lint clean                        |
| `php artisan route:list`                | ✅ 38 route, 3 throttle terverifikasi                        |
| `php artisan migrate:fresh --seed`      | ✅ 20 migration + seed                                       |
| Spec review                             | ✅ setelah 2 Important diperbaiki                            |
| Code quality review                     | ✅ Changes requested → 2 Important + nits diperbaiki → hijau |

## File Dibuat

Policies:

- `app/Policies/NovelPolicy.php` — `view` (published only, NO owner bypass), `viewForAuthor` (owner|moderator, untuk Studio), `viewCover` (published→true, else owner|moderator), `update`/`delete` (owner), `create` (auth), `submitForReview` (owner + status Draft|RevisionRequired)
- `app/Policies/ChapterPolicy.php` — `view` (chapter Published AND novel Published), `create`, `update` (owner + Draft), `publish` (owner + novel Published + Draft)
- `app/Policies/InitialSubmissionPolicy.php` — `viewAny`, `view` (moderator|owner novel), `review` (moderator AND NOT submitter)

Audit log:

- `app/Domain/Moderation/Contracts/ModerationActionRepository.php`
- `app/Application/Moderation/Commands/RecordModerationAction/RecordModerationActionHandler.php` + `Command`
- `app/Infrastructure/Persistence/Repositories/EloquentModerationActionRepository.php`
- hooked DALAM `DB::transaction` yang sama dengan keputusan → audit gagal = rollback semua

Tests:

- `tests/Feature/Authorization/AuthorizationTest.php` — matriks Rules §4 (guest/member/owner/moderator × resource, plus invalid-payload 403)
- `tests/Feature/ModerationAuditTest.php` — audit row per keputusan + failure-injection rollback test
- `tests/Feature/RateLimitTest.php` — boundary exact 10/30/60 + user isolation
- `tests/Feature/ValidationHardeningTest.php`

## File Diubah

- `app/Application/Moderation/Commands/ModerateInitialSubmission/ModerateInitialSubmissionHandler.php` — audit call + lockForUpdate dipertahankan
- `app/Http/Controllers/{NovelController,ReaderController,NovelCoverController,ReaderInteractionController}.php` — policy-driven
- `app/Http/Controllers/Moderation/ModerationQueueController.php` — policy + defence in depth
- `app/Http/Controllers/Studio/{StudioNovelController,StudioChapterController}.php` — policy-driven
- `app/Http/Requests/Moderation/ModerateSubmissionRequest.php` — `authorize()` resolve submission + `Gate::allows('review')`
- `app/Http/Requests/Studio/{StoreChapterRequest,UpdateChapterRequest,SubmitReviewRequest}.php` — `authorize()` policy resource SEBELUM rules
- `app/Http/Requests/Studio/StoreNovelRequest.php` — enum rule
- `app/Http/Requests/Reader/{SaveProgressRequest,BookmarkRequest,RemoveBookmarkRequest}.php` — position min 0
- `app/Providers/AppServiceProvider.php` — 3 `RateLimiter::for` (user-id + IP fallback)
- `routes/web.php` — `throttle:submit-review` (10/min), `throttle:moderation-decision` (30/min), `throttle:reader-progress` (60/min)
- tests lama: `Auth/LoginTest.php`, `Moderation/ModerationQueueTest.php`, `Studio/StudioTest.php`

## Keputusan Penting

- **404 vs 403 mapping**: public catalog route (novel/chapter detail+read) = **404 untuk semua** bila non-published (policy `view` published-only, TANPA bypass owner/moderator) — mencegah kebocoran via URL guessing. Studio route = **403** untuk stranger (policy `viewForAuthor`). Cover = **403** unauthorized / **404** file hilang, dan authorization dicek SEBELUM file existence.
- **Authorization sebelum validation**: FormRequest `authorize()` resolve route-bound model + `Gate::allows(...)`, sehingga user tidak entitled dapat 403 (bukan 422) walau payload malformed. Controller tetap cek policy sebagai defence in depth.
- **Audit**: `target_type` = `InitialSubmission` (yang dimoderasi), `action` = decision value, `reason` = review notes, `metadata` = `{novel_id, submission_id, decision}`. Insert di DALAM transaction → consistency.
- **Rate limit keys**: user id dengan IP fallback; test isolation via `Cache::flush()` (key aslinya di-hash Laravel).

## Risiko Sisa (untuk fase berikutnya / pre-prod)

- **Row lock (`lockForUpdate`) belum diuji di MySQL** — SQLite dev tidak support. Wajib uji di MySQL sebelum prod.
- **Belum ada test E2E/browser** — hanya feature test + build.
- **Novel update/delete HTTP route belum ada** — policy method sudah ada, route menyusul saat Studio edit novelBlocks/timeline/notes. Silakan cek.
- **Policy lazy-load** (`$chapter->novel`, `$submission->novel`) menambah query — N+1 risk jika dipakai di collection. Minor untuk sekarang.
- **Catatan minor ditunda**: normalisasi notes whitespace→null untuk approved, JSON branch di FormRequest (tidak ada API route), scroll position tracking.
- **Belum ada**: komentar, reports, notifikasi, audit berkala, statistik (semua P1); monetisasi/Ink (P2, non-goal MVP).

## Status Fase 0/1: SELESAI

Semua Definition of Done MVP ter-cover: tulis → submit → review → terbit → baca, pengunjung baca gratis, anggota bisa simpan/continue, penulis kelola draft, moderator punya antrean + audit trail, tanpa fungsi pembayaran/Ink aktif, test untuk use case + authorization ada, error/loading/empty/success state ada.

**Langkah berikutnya** (bukan task, keputusan produk): P1 (komentar, reports, notifikasi, audit berkala, statistik) atau hardening pre-prod (uji MySQL, E2E, backup).
