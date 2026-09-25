# Fiksia — implementation brief dan prompt OpenCode

Target: https://github.com/LunaeKim99/fiksia

> KEAMANAN: Screenshot kredensial Google pernah dibagikan. Rotate/regenerate Google OAuth client secret di Google Cloud Console, ganti nilai di `.env` lokal, jangan pernah masukkan secret sebenarnya ke prompt, chat, commit, screenshot, atau log. Jangan otomatis melakukan commit/push.

## Sebelum menjalankan agent

1. Pastikan di Google Cloud Console OAuth client bertipe **Web application**. Authorized redirect URI untuk development harus persis `http://localhost:8000/auth/google/callback`; authorized origin bila diminta `http://localhost:8000` (bukan URL callback). Tambah akun sendiri sebagai test user jika aplikasi masih testing.
2. `.env` lokal berisi `APP_URL=http://localhost:8000`, `GOOGLE_CLIENT_ID=...`, `GOOGLE_CLIENT_SECRET=...`, `GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback` secara eksplisit. Jangan bergantung pada interpolasi `${APP_URL}` saat debugging. Pastikan browser memakai `localhost:8000`, bukan `127.0.0.1:8000`, untuk menjaga cookie sesi.
3. Jalankan `php artisan optimize:clear`. Jangan kirim nilai secret ke agent; agent cukup membaca nama variabel. Jangan copy `.env` ke repo.
4. Jalankan OpenCode dari root repository dan tempel prompt di bawah. Izinkan agent memasang `laravel/socialite` hanya jika belum tersedia, lalu jalankan migrasi dan tes setelah memeriksa diff.

## Prompt siap tempel untuk OpenCode

```text
Kamu adalah senior Laravel + React/TypeScript engineer. Implementasikan dua pekerjaan pada repo lokal Fiksia ini: (A) autentikasi Google dan (B) halaman Settings nyata beserta sistem tema single/dual/gradient dan fitur pengaturan lain yang benar-benar bekerja. Jangan sekadar menulis rencana; edit berkas dan tes. Jangan commit/push atau expose isi .env, client secret, token, dan identitas user ke output. Jika belum tersedia, boleh install laravel/socialite. Pertahankan arsitektur, route, model, validasi, UI, policy, dan konvensi yang telah ada.

PENTING: audit repo terlebih dahulu. Baca composer.json, package.json, routes/web.php, config/services.php, app/Models/User.php, app/Models/Profile.php, migrasi users dan profiles, controller Auth/AuthenticatedSessionController.php dan RegisteredUserController.php, Account.tsx, Login.tsx, Register.tsx, app.tsx, PublicLayout.tsx, ThemeToggle.tsx, resources/css/app.css, reader, navbar, dan tests. Identifikasi router SPA dan bentuk respons data yang sudah dipakai. Jangan asumsikan Inertia atau Sanctum; cek kode sebenarnya. Jangan mengubah model session authentication yang sudah dipakai. Jangan buat route /api dengan middleware auth:sanctum apabila aplikasi adalah SPA satu origin dengan web session.

A. GOOGLE LOGIN
- Tambahkan Socialite bila belum ada; tambahkan konfigurasi google di config/services.php menggunakan GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI, dan tulis hanya nama variabel + placeholder ke .env.example (tanpa nilai sungguhan). Pastikan redirect URI sesuai konfigurasi OAuth Cloud.
- Tambahkan route GET redirect dan GET callback di routes/web.php pada middleware web/session, guest saja bila sesuai struktur; beri nama route. Buat Google auth controller di namespace sesuai repo. Jalankan Socialite driver google secara stateful (JANGAN stateless() bila pakai session), minta hanya data identitas dasar yang dibutuhkan, handle user cancel/error tanpa menampilkan exception detail.
- Tambahkan migrasi aman untuk google_id unik dan nullable di users serta perubahan password jadi nullable HANYA jika registrasi Google memerlukannya; cek struktur aktual terlebih dahulu. Jangan paksa password acak kalau tidak diperlukan. Google avatar simpan pada field existing yang cocok atau kolom baru yang jelas bila belum ada. Jangan override avatar/nama/profil kustom user lama.
- Saat callback: cari berdasarkan google_id terlebih dahulu. Bila belum ditemukan dan email Google tersedia serta terverifikasi dengan aman, tentukan kebijakan link yang aman untuk akun email/password yang sudah ada: jangan secara diam-diam takeover akun existing hanya dari email tanpa verifikasi/konfirmasi yang cukup. Prefer flow: jika user sedang login, sediakan explicit connect dari Settings dengan konfirmasi dan cek google_id belum dipakai akun lain; jika guest dan email sudah terdaftar, tampilkan pesan aman untuk login metode lama lalu hubungkan dari Settings. Jika user baru, buat user dengan email unik, google_id, status/role default dan data wajib lainnya sesuai migrasi; buat profile default jika alur existing memerlukannya. Tolak akun disabled/banned sesuai logika existing. Simpan perubahan akun + profile secara atomik bila diperlukan. Login dengan Auth::login, regenerasi session, redirect ke URL internal yang aman (hindari open redirect). Google-only account harus bisa login ulang dan tidak bisa memakai password kosong untuk masuk.
- Tambahkan tombol Google dengan ikon yang sesuai di Login.tsx dan Register.tsx: navigasi browser penuh ke URL backend, bukan fetch OAuth. Error dari redirect ditampilkan dengan pola flash message yang sudah ada. Tambahkan status connected pada Settings Account, opsi connect/disconnect jika aman, dan cegah unlink bila akan menghilangkan satu-satunya metode login. Jangan menyimpan access/refresh token Google bila tidak dipakai.
- Tambahkan feature tests: guest redirect, callback user baru, login kembali user existing dengan google_id, email collision tanpa auto-link, connect Google saat login, user status invalid, callback error, session, serta cek role/profile existing bila relevan. Pakai Socialite fake/mocks sesuai versi yang terpasang; tes tidak boleh memanggil Google sungguhan.

B. SETTINGS + THEME + POLISH UI
- Implementasikan route /settings (atau routing frontend existing), protected by auth, navigasi dari account/avatar/sidebar, responsive UI; gunakan pola UI yang sudah ada. Lihat apakah Account.tsx sudah punya sebagian pengaturan supaya tidak membuat duplikat; bedakan public profile, account, dan settings.
- Buat settings yang PERSISTEN untuk user login; gunakan tabel user_settings dengan user_id unik dan JSON fields/kolom bertipe kuat ATAU profiles.settings JSON jika profile selalu tersedia. Jelaskan pilihan singkat dan tambahkan migration, model/casts, default normalizer, validation, authorization, dan tests. Jangan memakai array_merge top-level untuk update parsial nested yang bisa menimpa nilai tema/reading lain; gunakan validated patch per seksi dengan merge terkontrol. Ambil preference hanya dari user terautentikasi. Untuk guest, tema lokal dapat disimpan di localStorage tanpa endpoint.
- Kategori Settings: Tampilan (light/dark/system + single/dual/gradient, curated presets, warna custom, live preview, reset default), Membaca (ukuran font, serif/sans, line height, lebar kolom; terapkan di reader), Privasi (aktivitas baca/bookmark tampil publik hanya jika memang ada endpoint publik; jika tidak, tampilkan sebagai 'belum tersedia' bukan toggle palsu), Notifikasi (hanya preferensi bagi kanal yang betul-betul ada; jangan mengklaim pengiriman email/push berfungsi bila service belum ada), Akun (edit profil yang terhubung ke endpoint existing, keamanan login/provider; akun Google-only jangan diarahkan mengubah password sebelum ada flow set password). Bahasa hanya jika lokalisasi sudah ada; jika belum, tandai roadmap.
- Desain modern editorial + sedikit magical library yang terasa seperti platform novel, bukan dashboard generik. Perbaiki UI Home/Explore/card, navbar, auth, dan Settings dengan ruang baca, hierarchy visual, cover, genre, keadaan kosong/loading/error, mobile navigation, focus-visible, reduced motion. Pertahankan fitur existing, jangan overhaul yang memutus flow.
- Theme engine: pisahkan mode dasar (light/dark/system) dari accent style (single/dual/gradient). Single satu accent; dual dua accent berbeda per elemen; gradient hanya area dekoratif/CTA, jangan gradient untuk seluruh body atau teks panjang. Palet surface/text harus mengikuti mode demi kontras. Preview kartu tema dan seluruh app harus menggunakan token CSS yang sama. Gunakan CSS variables dan Tailwind/Vite conventions existing; dukung system change via matchMedia listener cleanup; hindari flash theme salah pada initial load jika memungkinkan. Simpan pilihan user login di server dan preview lokal di client; guest localStorage. Pastikan apply terjadi saat bootstrap/app navigation dan di reader. Sanitasi warna custom via format hex yang ketat dan batasan jumlah/ukuran input, jaga contrast teks pada tombol, fallback bila warna tidak aman, keyboard labels + aria-pressed bagi pilihan dan aria-label bagi color picker. Jangan inject nilai warna ke CSS dari string mentah tanpa validasi.
- Hindari layout bergantung pada kelas Tailwind dinamis yang tidak terkompilasi; gunakan CSS vars yang dipakai oleh komponen yang memang ada. Tidak boleh pakai 'any' tanpa alasan; TypeScript types untuk settings, API data, theme. Autosave debounce + status saving/saved/error/undo atau tombol Simpan dengan feedback jelas; jika request gagal, pulihkan nilai tersimpan atau tampilkan retry. Hindari race condition antara request update cepat; opsi: tombol Simpan per tab dengan state draft dan persist setelah sukses.

KETENTUAN IMPLEMENTASI
1. Kerjakan dalam fase audit -> auth -> settings persistence -> theme engine -> UI polish -> tests -> dokumentasi.
2. Jangan menghapus login email/password. Jangan tambah dependency yang tidak diperlukan.
3. Perbaiki regresi dari contoh saran sebelumnya: jangan pakai Inertia::render tanpa Inertia, jangan pakai auth:sanctum tanpa Sanctum, jangan asumsikan kolom profiles.bio, jangan tulis array_merge nested yang rusak, jangan jadikan seluruh settings updateable tanpa whitelist, jangan otomatis link akun email existing ke Google.
4. Jalankan php artisan route:list, php artisan migrate --pretend (bila supported), php artisan test, npm run build, dan linter/typecheck sesuai script project; laporkan status nyata dan error yang belum selesai. Tidak perlu menjalankan migrasi destructive pada data user tanpa persetujuan.
5. Tambahkan docs pengujian lokal manual: set Google OAuth Web client, redirect URI persis http://localhost:8000/auth/google/callback, testing users, php artisan optimize:clear, serve, jalankan Vite, alur login baru/lama/logout, setelan tema reload/device lain, browser/system theme, mobile, pembaca, error OAuth dan edge cases. Jangan tampilkan secret.
6. Di akhir ringkas file yang dibuat/diubah, keputusan migrasi, keamanan account linking, daftar tes dan hasil, serta semua fitur yang masih 'belum tersedia' tanpa berpura-pura sudah berfungsi.
```

## Setelah agent selesai

```bash
php artisan optimize:clear
php artisan route:list
php artisan migrate
php artisan test
npm run build
php artisan serve --host=localhost --port=8000
```

Buka browser di `http://localhost:8000` (bukan 127.0.0.1 bila URI OAuth didaftarkan sebagai localhost), lalu test Google login dan Settings. Jalankan `npm run dev` di terminal lain jika proyek membutuhkan Vite dev server. Bila `redirect_uri_mismatch`, cocokkan URL yang dikirim ke Google dengan authorized redirect URI persis, termasuk protokol, hostname, port, path, dan trailing slash. Bila `invalid_state`, periksa cookie/session dan konsistensi hostname; jangan 'memperbaiki' dengan stateless pada aplikasi sesi.
