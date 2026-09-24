# Evaluasi Task 5a — Studio Penulis

> Dibuat otomatis setelah Task 5a selesai. Untuk handoff sesi: baca file ini lalu lanjutkan ke Task 5b/5c.

## Ringkasan

Studio Penulis: Novel/Chapter CRUD, draft editor dengan autosave, submit initial review, publish chapter, cover upload aman. Semua action dibungkus try/catch DomainException → flash error. Ownership dijaga di controller (read) dan handler (write).

## Status Akhir

| Gate                                    | Hasil                                                |
| --------------------------------------- | ---------------------------------------------------- |
| `composer test` (pint + phpstan + pest) | ✅ pint pass, phpstan 0, pest 48/48 (176 assertions) |
| `npm run types:check`                   | ✅ pass                                              |
| `npm run check`                         | ✅ 46 file format, 28 file lint clean                |
| `php artisan route:list`                | ✅ 26 route termasuk `studio*` + `novels.cover`      |
| `php artisan migrate:fresh --seed`      | ✅ 13 migration, 8 genre                             |
| Spec review                             | ✅ Spec compliant                                    |
| Code quality review                     | ✅ Approved dengan fix (2 item diperbaiki)           |

## File Dibuat

Application (handler baru):

- `app/Application/Chapter/Commands/UpdateDraftChapter/UpdateDraftChapterHandler.php` + `UpdateDraftChapterCommand.php` — ownership check, DRAFT-only guard (InvalidStatusTransitionException: 'Draft yang sudah terbit tidak dapat diedit.'), `ChapterRepository::update`

Presentation backend:

- `app/Http/Controllers/Studio/StudioController.php` — dashboard novel penulis (guest → isGuest:true)
- `app/Http/Controllers/Studio/StudioNovelController.php` — create/store/show/submitReview
- `app/Http/Controllers/Studio/StudioChapterController.php` — create/store/edit/update/publish
- `app/Http/Controllers/NovelCoverController.php` — stream cover (published→publik, else owner 403, 404 missing, `ponytail:` role Task 6)
- `app/Http/Requests/Studio/StoreNovelRequest.php` — cover `image|mimes:jpg,jpeg,png,gif,webp|max:2048`, title/synopsis/story_status/genreIds/tagIds
- `app/Http/Requests/Studio/StoreChapterRequest.php`, `UpdateChapterRequest.php`, `SubmitReviewRequest.php`

Frontend:

- `resources/js/pages/Studio/Index.tsx` (menggantikan `pages/Studio.tsx` yang dihapus), `types.ts`
- `resources/js/pages/Studio/Novels/Create.tsx` — useForm, cover preview, genre checkboxes
- `resources/js/pages/Studio/Novels/Show.tsx` — chapter list, status badge, submit review (window.confirm), publish
- `resources/js/pages/Studio/Chapters/Create.tsx` — form judul+content, word count
- `resources/js/pages/Studio/Chapters/Edit.tsx` — autosave 2s debounce, beforeunload + dirty chip, Simpan Draft/Terbitkan

Tests:

- `tests/Feature/Studio/StudioTest.php` — 20 test: create novel+slug+genres, guest redirect, non-owner 403, chapter numbering 1→2, draft update (owner OK/non-owner 403/published blocked flash), submit review, publish OK/blocked, cover upload valid/bad ext/oversize, empty title 422

## File Diubah

- `routes/web.php` — 10 route studio + `GET /novels/{novel}/cover`; `/studio` dipindah ke controller, tetap publik dengan isGuest
- `resources/js/{actions,routes}/` — regenerasi wayfinder

## Keputusan Penting

- **Error flash pattern**: semua action controller wrap `try/catch DomainException` → `->back()->with('error', $e->getMessage())`. Exception lain (bug) tetap 500.
- **Cover upload**: private disk `local`, folder `covers`, filename `Str::uuid().ext`, ekstensi dari `guessExtension()` (MIME-derived, bukan client filename). Authz: published→publik, else owner.
- **Reader/editor**: Editor Studio punya chrome penuh PublicLayout; pakai `PublicLayout` + `beforeunload` guard.
- **UpdateDraftChapter**: handler+command terpisah di `App\Application\Chapter\Commands\UpdateDraftChapter\` (mengikuti pola CreateDraftChapter), bukan ekstensi handler lama.
- **Route `/studio` publik** (nav tidak break untuk guest), action route wajib auth.

## Risiko Sisa

- Belum ada Policy (Task 6) — hanya ownership check manual.
- Novel detail public belum ada (Task 5b) — tombol dari Studio masih ke halaman kosong.
- `popular` sort belum (Task 5b) — currently undefined.
- Cover upload validasi `image` rule cukup, tapi belum ada content-type sniffing defense-in-depth (minor, local disk tidak executed).

## Lanjut Ke Task 5b

Task 5b: Home (real published novels), Explore (search+filter+sort+pagination), Novel detail (public published only), Reader (public, settings, prev/next, drawer). Butuh: query `status=published`, `withCount('chapters')`, `resources/js/types/catalog.ts`, `NovelCard.tsx`, `tests/Feature/Catalog/`.
