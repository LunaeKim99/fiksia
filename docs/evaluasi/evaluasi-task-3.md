# Evaluasi Task 3 — Domain Foundation

> Dibuat otomatis setelah Task 3 selesai. Untuk handoff sesi: baca file ini (tidak perlu baca ulang kode lengkap) lalu lanjutkan ke Task 4.

## Ringkasan

6 enum status pure PHP (zero Illuminate import), 12 migration + kolom users baru, 9 model Eloquent + User diperbarui, 9 factory, seeder 8 genre. MVP free phase — tanpa tabel monetisasi.

## Status Akhir

| Gate                                     | Hasil                                                                               |
| ---------------------------------------- | ----------------------------------------------------------------------------------- |
| `php artisan migrate:fresh --seed`       | ✅ 15 migration + seed bersih                                                       |
| `php artisan test`                       | ✅ 11/11 pass                                                                       |
| `composer test` (pint + phpstan + pest)  | ✅ semua hijau (2 error pre-exist Auth Request sudah di-fix via docblock di Task 3) |
| `npm run types:check`                    | ✅ pass (frontend tak tersentuh)                                                    |
| `npm run check`                          | ✅ 34 file format, 23 file lint clean                                               |
| `php artisan model:show Novel`           | ✅ relasi + enum cast + slug hook OK                                                |
| Tinker sanity (pivot, morph, slug, enum) | ✅ pass                                                                             |
| Spec review                              | ✅ lulus setelah 4 relasi rusak diperbaiki                                          |
| Code quality review                      | ✅ Approved with minor changes → 7 Important sudah di-fix                           |

## File Dibuat

Enums (`app/Domain/`, pure PHP):

- `Novel/Enums/NovelStatus.php` — draft, initial_review, published, revision_required, suspended, archived, rejected
- `Novel/Enums/StoryStatus.php` — ongoing, completed, hiatus
- `Chapter/Enums/ChapterStatus.php` — draft, published, hidden, archived
- `Moderation/Enums/SubmissionStatus.php` — submitted, reviewing, approved, revision_required, rejected (+ `ponytail:` Reviewing reserved untuk claim-for-review depan)
- `Moderation/Enums/AuditStatus.php` — planned, in_review, passed, revision_required, restricted, removed
- `Moderation/Enums/SubmissionItemType.php` — cover, synopsis, metadata, chapter

Migrations (`database/migrations/2026_09_24_000001–000012`):

- users: `birth_date` nullable + `status` default 'active'
- profiles, novels (index user/status/story/published_at, soft delete), chapters (unique novel_id+chapter_number, soft delete), genres, tags, novel_tags, novel_genres (pivot asumsi A1), initial_submissions, submission_items, content_audits, moderation_actions (`morphs('target')`)

Models (`app/Models/`, gaya `#[Fillable]`, `casts(): array`, HasFactory, enum cast):

- `Profile`, `Novel` (slug hook + `withTrashed` check + `ponytail:` race), `Chapter`, `Genre`, `Tag`, `InitialSubmission`, `SubmissionItem`, `ContentAudit`, `ModerationAction` (morphTo `target()`)
- Novel: `belongsToMany(..., 'novel_genres')` eksplisit (default Laravel salah); invers `novels()` di Genre/Tag; `contentAudits()` di Novel+Chapter

Factories (9) + `DatabaseSeeder` (8 genre: Fantasi, Romansa, Aksi, Misteri, Drama, Sci-Fi, Horor, Komedi, `firstOrCreate`).

## File Diubah

- `app/Models/User.php` — cast `birth_date`, fillable, relasi profile/novels; hapus komentar scaffold
- `database/factories/UserFactory.php` — `birth_date` optional + `status` active eksplisit
- Factories pakai enum `::->value` (bukan string mentah)
- Migration `profiles`: `theme_preference` default 'system' TANPA nullable (default ⇒ not null)
- `foreignId()->constrained()` tanpa `->index()` eksplisit (sudah ter-index otomatis)

## Keputusan Penting

- **A1 novel_genres pivot**: Schema ambigu (ada tabel genres tapi tanpa genre_id/FK) → pilih pivot many-to-many untuk filter Explore. APPROVED.
- **A2 slug**: `creating` hook `Str::slug` + loop unik (`slug`, `slug-1`, …); Novel cek `withTrashed()` (soft-deleted slug tetap reserved); `ponytail:` race konkuren.
- **A3 moderation target**: `morphs('target')` + `ModerationAction::target(): MorphTo`.
- **A4 FK**: owning children `cascadeOnDelete`, ref user nullable `nullOnDelete`.
- **A5 users.status**: string biasa 'active', tanpa enum.
- `SubmissionStatus` ikut Schema.md (tanpa 'draft'); `Reviewing` tidak di-set handler manapun (langsung Submitted→Approved untuk MVP).

## Kualitas Terverifikasi

- `grep Illuminate app/Domain` → kosong.
- phpstan scoped 0 error; pint pass; tanpa `//` komentar (kecuali docblock larastan + `ponytail:`).
- Tinker: attach Genre+Tag via pivot OK dua arah; morphTo resolve ke Novel; slug auto; enum resolve.

## Risiko Sisa

- `nextChapterNumber` max+1 race konkuren (`ponytail:` — butuh SELECT FOR UPDATE / retry di MySQL prod).
- `ChapterFactory` `chapter_number` unik global (harusnya per-novel) — terima untuk MVP; Task 4 scope sequence per novel.
- Drift SQLite dev / MySQL prod — verifikasi ulang di MySQL sebelum prod.
- `chapter_revisions` (Schema §3) ditunda — versi revisi belum ada; draft vs published via `chapters.status` untuk sekarang.

## Lanjut Ke Task 4

Task 4 membutuhkan: contracts + entities + exceptions di `app/Domain/`, repository Eloquent di `app/Infrastructure/`, handler + DTO di `app/Application/`, binding DI di AppServiceProvider, tests Unit + InitialReviewFlowTest.
