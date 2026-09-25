# Evaluasi Task 1 — Google Auth Backend

Tanggal: 2026-09-25
Status: Selesai dan disetujui reviewer

## Files

Dibuat:

- `app/Domain/Shared/Enums/UserStatus.php`
- `app/Domain/Shared/ValueObjects/GoogleIdentity.php`
- `app/Application/Auth/Contracts/GoogleAuthProvider.php`
- `app/Infrastructure/Auth/SocialiteGoogleAuthProvider.php`
- `app/Application/Auth/Services/GoogleAccountService.php`
- `app/Http/Middleware/EnsureActiveUser.php`
- `app/Http/Controllers/Auth/GoogleAuthController.php`
- `database/migrations/2026_09_25_000006_add_google_auth_fields_to_users_table.php`
- `tests/Unit/Auth/UserStatusTest.php`
- `tests/Feature/Auth/GoogleAuthTest.php`

Diubah:

- `config/services.php`
- `.env.example`
- `app/Models/User.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `bootstrap/app.php`
- `routes/web.php`
- `database/factories/UserFactory.php`

## Migration

- Menambahkan `google_id` nullable dengan unique index.
- Menambahkan `google_avatar_url` nullable.
- Mengubah `users.password` menjadi nullable tanpa random password.
- Rollback memeriksa Google-only user sebelum perubahan destruktif pada semua driver.
- Verifikasi berhasil pada SQLite in-memory dan disposable MySQL 8.4.3.

## Account Linking Decision

- Lookup login melakukan `google_id` terlebih dahulu.
- Email collision tidak melakukan auto-link.
- Guest harus login dengan metode lama lalu melakukan connect eksplisit dari settings.
- Connect memerlukan user active, email provider terverifikasi dan cocok, serta `google_id` belum dipakai.
- Disconnect hanya boleh jika password masih tersedia.
- User Google-only memakai `password=null`.

## Security

- Socialite stateful; tidak ada `stateless()`.
- Token access/refresh tidak disimpan atau diserialisasi.
- `google_id` tidak masuk ke `auth.user` Inertia.
- Redirect callback memakai fixed internal destination.
- Callback dan initiation error memakai flash message generik.
- Protected routes memakai `auth` dan `active`.
- Protected dirty files `composer.json`, `composer.lock`, dan brief tidak diubah oleh task ini.

## Tests

- Focused Google/Auth/UserStatus/Login/Registration: **43/43 passed**, 152 assertions.
- Full suite: **168/168 passed**, 840 assertions.
- `pint` clean.
- `git diff --check` exit 0; hanya warning CRLF bawaan pada `.env.example`.
- Tidak ada network call ke Google.

## Route Evidence

Route yang tersedia:

- `GET /auth/google/redirect` → `auth.google.redirect`
- `GET /auth/google/callback` → `auth.google.callback`
- `GET /settings/account/google/connect` → `settings.account.google.connect`
- `POST /settings/account/google/disconnect` → `settings.account.google.disconnect`

## Known Limits

- SQLite rollback tetap tidak mengembalikan `password` menjadi non-null setelah guard aman; ini tidak menghapus mapping Google ketika Google-only user ada.
- Case-insensitive email lookup memakai `LOWER(email)` dan dapat perlu functional index pada skala besar.
- Tidak ada set-password flow; disconnect Google-only tetap diblokir.
- Tidak ada profile row yang dibuat otomatis oleh Google registration.

## Handoff

Task 2 dimulai setelah evaluation ini: Google Auth UI. Backend contract yang dipakai:

- `GoogleAuthProvider`
- `GoogleIdentity`
- `GoogleAccountService`
- `User::isActive()`
- `User::canDisconnectGoogle()`
- `active` middleware alias
- route `auth.google.redirect`
