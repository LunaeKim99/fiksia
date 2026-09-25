# Evaluasi Task 3 — Settings Persistence

Tanggal: 2026-09-25
Status: Selesai dan disetujui reviewer

## Schema Decision

- Tabel `user_settings` dipakai karena `Profile` optional.
- `user_id` unique, foreign key cascade delete.
- Empat section JSON: `appearance`, `reading`, `privacy`, `notifications`.
- Tidak ada perubahan pada legacy profile preference columns.

## Defaults

- Appearance: system, single, violet, `#7C3AED`, secondary null.
- Reading: normal, sans, `1.7`, normal.
- Privacy dan notification: null sampai service nyata tersedia.

## Allowlists

- Mode: `light|dark|system`.
- Accent style: `single|dual|gradient`.
- Preset: `violet|ember|forest|ocean|rose|custom`.
- Warna custom: strict `#RRGGBB`.
- Reading: enum dan range sesuai brief.

## Section Isolation

- Satu section per PATCH.
- Section lain tidak ditimpa.
- Unknown key dan `user_id` dari client ditolak.
- Nilai invalid di storage diperbaiki ke default saat read.
- Privacy/notification writes ditolak.

## Authorization

- `GET /settings` protected `auth` + `active`.
- `PATCH /settings/{section}` protected `auth` + `active`.
- Tidak ada API route atau Sanctum.

## Tests

- Focused settings: 17/17.
- Focused settings + Google auth: 49/49.
- Pint clean.
- `git diff --check` exit 0.
- Route list dan migration pretend terverifikasi.

## Migration Evidence

`user_settings` migration hanya membuat tabel settings dan tidak menyentuh `profiles`.

## Known Limits

- `resources/js/pages/Settings/Index.tsx` belum dibuat; Task 5 memiliki frontend UI.
- Task 1 migration tetap muncul pada `migrate --pretend` karena shared migration history.

## Handoff

Task 4 dapat dimulai setelah evaluation final ini.
