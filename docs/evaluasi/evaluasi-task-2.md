# Evaluasi Task 2 — Auth Scaffold

> Dibuat otomatis setelah Task 2 selesai. Untuk handoff sesi: baca file ini (tidak perlu baca ulang kode lengkap) lalu lanjutkan ke Task 3.

## Ringkasan

Register, login, logout (Inertia + session). Form Request validasi. Rate limit login 5/menit (email|ip) + register 30/jam (IP). Feature tests Pest. Copy bahasa Indonesia.

## Status Akhir

| Gate                                    | Hasil                                                                                         |
| --------------------------------------- | --------------------------------------------------------------------------------------------- |
| `composer test` (pint + phpstan + pest) | ✅ pint pass, phpstan 0 error, pest 11/11 pass (34 assertions)                                |
| `npm run types:check`                   | ✅ pass                                                                                       |
| `npm run check`                         | ✅ 34 file format, 0 warnings 23 file                                                         |
| `php artisan route:list`                | ✅ GET/POST register+login, POST logout, middleware guest/auth benar                          |
| Spec review                             | ✅ Spec compliant                                                                             |
| Code quality review                     | ✅ Approved (2 nit Minor: rate limiter ganda, logout tanpa flash — dibiarkan, tidak breaking) |

## File Dibuat

- `app/Http/Controllers/Auth/RegisteredUserController.php` — `store(RegisterRequest)`: `User::create`, `auth()->login`, redirect `home` + flash 'Akun berhasil dibuat.'
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — `create/store/destroy`: `Auth::attempt` + remember, `session()->regenerate()`, logout `invalidate` + `regenerateToken`, redirect login/logout + flash
- `app/Http/Requests/Auth/RegisterRequest.php` — name/email/password(confirmed, min 8); tambah `/** @return array<string, array<int, mixed>> */` agar phpstan `missingType.iterableValue` hijau
- `app/Http/Requests/Auth/LoginRequest.php` — email/password/remember; `authenticate()` + `ensureIsNotRateLimited()` (Breeze pattern, ValidationException 'Kredensial tidak valid.'); tambah docblock phpstan yang sama
- `resources/js/pages/auth/Login.tsx` — `useForm`, `remember` checkbox, `.field-error` per field, loading state, link ke register, `<Head title="Masuk">`
- `resources/js/pages/auth/Register.tsx` — name/email/password/confirm, link ke login, `<Head title="Daftar">`
- `tests/Feature/Auth/RegistrationTest.php` — lihat halaman, register valid, gagal validasi, email duplikat, password mismatch
- `tests/Feature/Auth/LoginTest.php` — lihat halaman, login valid, kredensial salah (422 + guest)
- `tests/Feature/Auth/LogoutTest.php` — logout → redirect login + guest + session hilang

## File Diubah

- `routes/web.php` — tambah 5 route auth (`register`, `login`, `logout`), group `guest`/`auth`, middleware `throttle:login`/`throttle:register`
- `app/Providers/AppServiceProvider.php` — `RateLimiter::for('login')` + `::for('register')`
- `resources/js/pages/Account.tsx` — cabang auth (nama + tombol logout via `router.post`) vs guest (ajakan login)
- `database/factories/UserFactory.php` — (Task 3 menambah `birth_date`/`status` eksplisit di file ini; lihat evaluasi-task-3)

## Keputusan Penting

- Rate limit ganda: `throttle:login` di route + `ensureIsNotRateLimited()` di LoginRequest — efektif 5/menit lewat yang fire duluan; key prefix beda tapi tidak masalah keamanan. Dibiarkan (standard Breeze hanya di Request; route-level tambahan).
- Logout tanpa flash — dibiarkan (spec opsional).
- `Auth::attempt` + session regen/invalidate — posture benar; tanpa user enumeration (pesan sama untuk email salah/password salah).

## Kualitas Terverifikasi

- Controller tipis; semua logika di Form Request + Auth facade.
- Rate limiter aktif dan dipakai di middleware route.
- React: `useForm`, `processing` state, `.field-error`, label+htmlFor, remember checkbox, password confirm.
- Zero comment di file tangan; tanpa `any`; tanpa dependensi baru.

## Risiko Sisa

- Ganda rate limiter (Minor, bukan masalah keamanan).
- Logout tanpa konfirmasi flash (Minor UX).

## Lanjut Ke Task 3

Task 3 membutuhkan: `app/Domain/**/Enums/`, 12 migration, `app/Models/` baru, factories, seeder 8 genre. Users migration sudah ada (Task 2 tidak mengubah schema).
