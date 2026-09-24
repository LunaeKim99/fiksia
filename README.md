# Fiksia

Platform web novel komunitas. Pengunjung bisa baca novel gratis tanpa akun. Pengguna terdaftar bisa mengikuti, menyimpan, bookmark, dan melanjutkan bacaan. Penulis bisa menulis dan menerbitkan lewat Studio. Moderator memeriksa pengajuan lewat antrean.

Versi sekarang: **MVP gratis selesai**. Fitur pembayaran (Ink) belum aktif.

## Stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | React 19, Inertia.js 3, TypeScript |
| Styling | Tailwind CSS 4 |
| Build | Vite 8 |
| Database | MySQL (produksi), SQLite (dev) |
| Test | Pest, PHPStan, Pint |

## Kebutuhan Sistem

- PHP 8.3 atau lebih baru (-developed dengan 8.5)
- Composer 2
- Node.js 20 atau lebih baru
- MySQL (produksi) atau SQLite (dev, default)

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# dev pakai SQLite
php artisan migrate --seed
```

## Menjalankan

Butuh dua terminal:

```bash
php artisan serve
```

```bash
npm run dev
```

Buka **http://localhost:8000**

## Perintah

```bash
composer test        # test + format check + type check PHP
npm run types:check  # type check TypeScript
npm run check        # lint + format check frontend
npm run build        # build produksi
npm run check:fix    # perbaiki format otomatis
```

## Struktur Folder

```text
app/
  Domain/          aturan bisnis murni (tidak bergantung Laravel)
  Application/     use case, DTO, kontrak repository
  Infrastructure/  implementasi Eloquent, repository
  Http/            controller, request, response
  Models/          model Eloquent
  Policies/        aturan akses (server-side)

resources/js/
  pages/           halaman Inertia
  components/      komponen UI dan navigasi
  layouts/         layout bersama
  types/           tipe TypeScript

database/
  migrations/      struktur tabel
  factories/       data dummy untuk test
  seeders/         data awal (genre)

docs/              spesifikasi produk dan arsitektur
tests/             test Pest (Feature + Unit)
```

## Alur Utama

```text
Penulis buat novel
  → tulis beberapa bab (draft, tersimpan otomatis)
  → kirim pengajuan awal
  → moderator: approve / minta revisi / tolak
  → novel terbit, bab lanjutan bisa terbit tanpa review ulang
  → pembaca baca, ikuti, simpan, lanjut dari progress
```

Kalau ditolak atau diminta revisi, penulis memperbaiki lalu mengirim ulang.

## Cara Mencoba

1. Buka `/register`, buat akun
2. Buka `/studio`, buat novel: isi judul, sinopsis, pilih genre, unggah cover
3. Dari detail novel di Studio, tambah bab
4. Kirim pengajuan awal
5. Jadikan akun jadi moderator, lalu buka `/moderation/queue` untuk memeriksa

```bash
php artisan tinker
```

```php
App\Models\User::where('email', 'email-kamu@contoh.com')->update(['role' => 'moderator']);
```

Role: `member` (bawaan), `moderator`, `admin`.

## Alamat Penting

| URL | Isi |
|---|---|
| `/` | Beranda, novel terbaru, lanjut baca |
| `/explore` | Cari novel, filter genre dan status |
| `/novels/{slug}` | Detail novel dan daftar bab |
| `/novels/{slug}/chapters/{nomor}` | Reader, pengaturan ukuran teks |
| `/register`, `/login` | Daftar dan masuk |
| `/library` | Rak: mengikuti, disimpan, dibaca, selesai (butuh akun) |
| `/studio` | Dashboard penulis |
| `/moderation/queue` | Antrean pemeriksaan (butuh moderator) |

## Catatan

- Role belum punya halaman pengelolaan, isi lewat `tinker` seperti contoh di atas.
- Upload cover maksimal 2 MB, format jpg, png, gif, webp.
- Draft, pengajuan pending, dan karya yang ditangguhkan tidak muncul di halaman publik.
- Monetisasi (Ink, pembelian akses, withdraw) belum dibuat. Aturan dan provider pembayaran harus selesai dulu sebelum diaktifkan.

## Dokumentasi

Spesifikasi lengkap ada di `docs/`:

| File | Isi |
|---|---|
| `docs/Prompt.md` | Prompt utama dan urutan pengerjaan |
| `docs/PRD.md` | Scope, user story, acceptance criteria |
| `docs/Architecture.md` | Layer dan Clean Architecture |
| `docs/Design.md` | Design system, navigasi, halaman |
| `docs/Schema.md` | Entitas dan relasi database |
| `docs/Rules.md` | Aturan pengembangan, keamanan, testing |
| `docs/evaluasi/README.md` | Ringkasan progres per task |
