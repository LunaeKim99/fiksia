# Evaluasi Task 7 — Audit Google Auth, Settings, dan Theme

Tanggal: 2026-09-25
Status: Selesai
Jenis: Read-only audit sebelum implementasi

## Hasil

Audit dipisahkan secara serial menjadi tiga bagian:

1. Backend auth dan data.
2. Frontend Inertia, navigation, reader, dan theme.
3. Tests, tooling, dan dirty-worktree risk.

Tidak ada file implementasi yang diubah. Tidak ada dependency baru dipasang. Tidak ada commit atau push.

## Temuan Utama

- Aplikasi memakai Laravel 13, Inertia 3, React 19, TypeScript, dan single-origin web session.
- `laravel/socialite` sudah ada di working tree pada versi 5.31.0.
- `users.password` masih non-nullable; Google-only account membutuhkan migration nullable.
- Belum ada `google_id`, Google config, route, controller, atau test.
- `/account` saat ini public dan berbeda dari settings protected.
- `Profile` bersifat optional; registration tidak membuat profile. Karena itu settings sebaiknya memakai tabel `user_settings` satu-ke-satu, bukan bergantung pada `profiles`.
- `status` default `active`, tetapi belum ada enum/policy/filter login yang konsisten.
- Tidak ada password reset/set-password flow; disconnect Google harus aman dan tidak boleh meninggalkan akun tanpa metode login.
- Reader memakai plain text aman dan localStorage `fiksia-reader-prefs`.
- Theme saat ini hanya light/dark/system melalui `appearance`; belum ada accent style atau server persistence.
- Tidak ada frontend test runner; verifikasi frontend memakai typecheck, lint/check, build, dan browser smoke test.

## Risiko

- `composer.json`, `composer.lock`, dan brief adalah dirty input yang harus dipertahankan.
- Jangan menjalankan dependency command yang dapat menulis ulang lock file tanpa necessity.
- Semua task implementasi harus serial karena ada hotspot bersama: routes, User, Account, app.tsx, dan CSS.
- Jangan auto-link akun email existing.
- Jangan memakai stateless OAuth, endpoint API terpisah, atau Inertia response yang tidak sesuai.

## Verifikasi

Audit read-only selesai. Belum menjalankan test suite karena belum ada perubahan implementasi.

## Task Berikutnya

1. Tetapkan kontrak design final dan urutan task SDD.
2. Implementasikan Google auth backend.
3. Review task, perbaiki temuan penting, lalu buat evaluation file task tersebut.
4. Lanjutkan settings persistence, theme engine, UI, dan regression secara serial.
