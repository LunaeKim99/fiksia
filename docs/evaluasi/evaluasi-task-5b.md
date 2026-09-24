# Evaluasi Task 5b — Public Catalog

> Dibuat otomatis setelah Task 5b selesai. Untuk handoff sesi: baca file ini lalu lanjutkan ke Task 5c/6.

## Ringkasan

Jalur baca/browse publik: Home (novel terbit terbaru), Explore (search + filter genre/status + sort + pagination), Novel detail (publik, hanya novel terbit), Reader (bab terbit, pengaturan baca, prev/next, chapter drawer). Draft/rejected/suspended/soft-deleted tidak pernah terlihat publik.

## Status Akhir

| Gate                                    | Hasil                                                                         |
| --------------------------------------- | ----------------------------------------------------------------------------- |
| `composer test` (pint + phpstan + pest) | ✅ pint pass, phpstan 0, pest 62/62 (338 assertions)                          |
| `npm run types:check`                   | ✅ tsc --noEmit clean                                                         |
| `npm run check`                         | ✅ 50 file format, 32 file lint clean                                         |
| `npm run build`                         | ✅ 601 modules, built 4.07s                                                   |
| `php artisan route:list`                | ✅ 28 route                                                                   |
| `php artisan migrate:fresh --seed`      | ✅ bersih                                                                     |
| Spec review                             | ✅ Spec compliant (4 issue Important ditemukan quality review → sudah di-fix) |

## File Dibuat

Backend:

- `app/Http/Controllers/HomeController.php` — 8 novel terbit terbaru, author, genres, jumlah bab terbit
- `app/Http/Controllers/ExploreController.php` — search (title/synopsis/author, LIKE ter-bind), filter genre slug, status story_status, sort latest/popular (proxy jumlah bab), pagination 12 + `withQueryString()`
- `app/Http/Controllers/NovelController.php` — detail published-only, `firstOrFail()` → 404 untuk non-published, chapter published saja dengan column selection
- `app/Http/Controllers/ReaderController.php` — reader published-only, chapter_list column selection ringan, prev/next

Frontend:

- `resources/js/types/catalog.ts` — tipe props katalog (tanpa `any`), di-export via `types/index.ts`
- `resources/js/components/catalog/NovelCard.tsx` — kartu novel reusable
- `resources/js/pages/Home.tsx` — replaced placeholder, real published novels + EmptyState
- `resources/js/pages/Explore.tsx` — replaced placeholder, search/filter/sort/pagination, filter state dipertahankan saat ganti filter, input sinkron dengan `activeFilters.q`
- `resources/js/pages/Novel/Show.tsx` — detail novel published, daftar bab, CTA Studio untuk owner, teks "fitur ikuti/simpan menyusul" (5c)
- `resources/js/pages/Reader/Show.tsx` — chrome minimal sendiri (bukan PublicLayout), konten teks `whitespace-pre-wrap` (auto-escape, NO dangerouslySetInnerHTML), pengaturan font size/line height/width via localStorage (try/catch), prev/next, chapter drawer, top bar slim + tombol kembali

Tests:

- `tests/Feature/Catalog/CatalogTest.php` — 13 test awal → total suite 62: guest home, explore search (title/synopsis/author terpisah), negative (draft/rejected/suspended/soft-deleted tidak muncul), detail 200/404, reader 200/404, cover auth, pagination 13+ novel → 2 halaman, guest baca 200, soft-deleted novel/chapter → 404

## File Diubah

- `routes/web.php` — `GET /` → HomeController, `GET /explore` → ExploreController, `GET /novels/{novel}` → NovelController@show, `GET /novels/{novel}/chapters/{chapter}` → ReaderController
- `app/Models/Novel.php`, `Chapter.php` — scope published (soft delete default)
- `app/Models/User.php` — relasi untuk author name
- `resources/js/pages/Home.tsx`, `Explore.tsx` — replaced placeholder
- `resources/js/{actions,routes}/` — regenerasi wayfinder

## Keputusan Penting

- **Slug resolution**: route param `{novel}` = slug, di-resolve manual di controller (`Novel::where('slug', ...) ->published()->firstOrFail()`) agar 404 (bukan 403/500) untuk non-published.
- **Reader chrome**: pakai top bar minimal sendiri, BUKAN PublicLayout — mode fokus baca (Design §5), nav global disembunyikan.
- **Popular sort**: proxy `withCount('chapters')` karena follows/saves belum ada (5c).
- **Konten reader**: teks biasa (whitespace-pre-wrap) — rich text/sanitizer ditunda; TIDAK pakai dangerouslySetInnerHTML.
- **Filter Explore**: `exploreUrl({ ...activeFilters, ...change })` → ganti satu filter tidak menghapus yang lain; input search sinkron via useEffect dengan `activeFilters.q`.
- **Column selection**: daftar bab di detail/reader query hanya kolom yang dipakai (hemat memori untuk novel panjang).
- **No follow/save buttons** di 5b — teks informational saja (5c).

## Risiko Sisa

- **Belum ada reading progress / continue reading** — Home tidak punya "Lanjut Baca" (butuh tabel reading_progress = 5c/6).
- **Belum ada Library/follow/save/bookmark** — 5c.
- **Tidak ada paywall** — `is_free` disimpan tapi belum dipakai; semua bab gratis di MVP.
- **Tidak ada rich text editor** — konten textarea polos, sanitasi HTML belum relevan (aman karena teks).
- **Tidak ada screenshot pass** di browser nyata — hanya build+test.
- **Tidak ada Policy** (Task 6) — public content guard via query scope, bukan Policy.

## Lanjut Ke Task 5c

Task 5c perlu: migration baru (follows, saved_novels, reading_progress, bookmarks — belum ada di Task 3), user role (moderator/admin), use case Library/Follow/Save/Progress, Moderator queue (butuh role check), Personal Library tab. Prioritas: Library (P0) dulu, Moderator queue perlu role system.
