# Rules — Aturan Pengembangan Platform Web Novel

## 1. Aturan umum

- Ikuti Clean Architecture.
- Gunakan Laravel + React + Inertia.js + TypeScript + Tailwind CSS + MySQL + Vite.
- Gunakan modular monolith untuk MVP.
- Jangan memakai Materialize CSS sebagai framework styling utama.
- Gunakan bahasa Indonesia pada UI; istilah teknis boleh tetap dalam bahasa Inggris jika lebih umum.
- Jangan membuat fitur di luar scope tanpa mencatatnya di PRD.
- Prioritaskan alur yang dapat diuji daripada banyak halaman kosong.

## 2. Aturan arsitektur

- Domain tidak boleh bergantung pada Laravel, Eloquent, HTTP, React, atau payment gateway.
- Application berisi use case, DTO, port/interface, dan orchestration.
- Infrastructure berisi implementasi database, storage, notification, cache, dan provider eksternal.
- Presentation berisi controller, request, resource, Inertia page, dan UI.
- Controller harus tipis.
- Jangan menaruh query database kompleks di React atau controller.
- Jangan memanggil Eloquent langsung dari Domain.
- Jangan menaruh aturan bisnis hanya di frontend.
- Semua authorization wajib divalidasi di server menggunakan Policy/Gate.
- Gunakan dependency injection dan service container binding untuk interface.

## 3. Aturan database

- Semua perubahan schema menggunakan migration.
- Gunakan foreign key, index, unique constraint, dan soft delete bila sesuai.
- Nama tabel teknis tidak bergantung pada branding currency.
- Status menggunakan enum PHP/konstanta yang konsisten.
- Konten draft dan konten terbit harus dapat dibedakan dengan jelas.
- Gunakan revision/version untuk perubahan konten publik yang memerlukan riwayat.
- Jangan menyimpan saldo keuangan hanya sebagai angka tanpa ledger.
- Semua perubahan finansial harus memiliki referensi transaksi dan audit.
- Gunakan integer untuk rupiah/Ink terkecil; jangan gunakan floating point.
- Operasi finansial harus atomic dengan database transaction dan idempotency.

## 4. Aturan autentikasi dan otorisasi

- Pengunjung tanpa akun dapat membaca bab gratis.
- Pengunjung tidak dapat save, bookmark, follow, membeli Ink, membeli akses, berkomentar, atau menulis.
- User terdaftar dapat menggunakan fitur personal dan Studio sesuai izin.
- Penulis hanya dapat mengubah karya miliknya.
- Moderator hanya dapat memproses antrean sesuai scope.
- Admin dapat mengubah konfigurasi platform.
- Draft, pending submission, data wallet, dan data pendapatan tidak boleh tersedia untuk publik.
- Jangan percaya role/permission dari request frontend.
- Uji akses positif dan negatif untuk setiap endpoint privat.

## 5. Aturan konten dan publikasi

- Target produk/konten: 16+.
- Pengajuan awal mencakup cover, sinopsis, metadata, dan beberapa bab awal.
- Karya belum tampil publik sebelum pemeriksaan awal disetujui.
- Bab lanjutan dapat diterbitkan tanpa ACC per bab setelah novel lolos pemeriksaan awal.
- Audit berkala dilakukan beberapa mingguan sesuai konfigurasi moderator.
- Laporan pengguna dapat diproses kapan saja, tidak menunggu jadwal audit.
- Keputusan approve, revision request, reject, restrict, atau remove harus memiliki alasan jika relevan.
- Penarikan karya, penghapusan, revisi, akses pembeli, dan refund membutuhkan kebijakan eksplisit sebelum monetisasi.
- Jangan mengarang statistik atau katalog saat platform kosong; gunakan empty state.
- Jangan mengindeks draft, pending, removed, atau konten yang tidak boleh publik.

## 6. Aturan editor dan Reader

- Autosave tidak boleh menghapus versi terakhir yang valid.
- Tampilkan status tersimpan/gagal menyimpan.
- Beri peringatan ketika user keluar dengan perubahan belum tersimpan.
- Reader harus menyediakan navigasi bab dan mode fokus.
- Reader mendukung light/dark/system dan pengaturan teks.
- Simpan progres hanya untuk user terdaftar; pengunjung boleh membaca tanpa sinkronisasi.
- Promosi internal 15 detik hanya ditempatkan di transisi antar-bab, tidak memotong teks.
- Promosi harus diberi label dan memiliki frekuensi yang dapat dikonfigurasi.

## 7. Aturan Ink dan monetisasi

- Gunakan nama tampilan **Ink** untuk currency internal.
- Nama tabel dan kolom teknis sebaiknya generik agar branding dapat diganti.
- Ink pembaca berbeda dari pendapatan penulis.
- Top-up menampilkan nilai Ink, biaya admin, dan total secara terpisah.
- Contoh paket:
    - Rp100.000 Ink + Rp5.000 biaya admin = Rp105.000.
    - Rp500.000 Ink + Rp10.000 biaya admin = Rp510.000.
- Fee top-up bukan komisi penjualan dan bukan biaya withdraw.
- Payment callback harus diverifikasi server-side.
- Jangan mengkreditkan Ink lebih dari satu kali untuk satu pembayaran.
- Harga dan paket hanya diambil dari konfigurasi server/admin.
- Pembelian akses menciptakan entitlement.
- Platform merencanakan komisi 5–10% dari penjualan; rumus final harus dikonfigurasi dan dicatat.
- Pendapatan penulis menunggu minimal 7 hari.
- Minimum withdraw rencana Rp50.000.
- Biaya bank/e-wallet withdraw dipisahkan dan ditampilkan.
- Pajak, aturan currency, payment gateway, refund, dan payout harus divalidasi sebelum monetisasi aktif.
- Jangan menyebut biaya sebagai “bebas pajak” tanpa tinjauan profesional.

## 8. Aturan keamanan

- Validasi semua input di server.
- Gunakan allowlist extension dan validasi MIME/content untuk upload.
- Batasi ukuran cover/avatar dan simpan dengan nama random/system-generated.
- Simpan upload di lokasi yang tidak dapat menjalankan script.
- Escape/render konten rich text dengan sanitizer yang tepat.
- Gunakan CSRF protection, rate limit, session security, password hashing, dan secure cookies.
- Jangan log password, token, secret, atau payload pembayaran sensitif.
- Secrets hanya di environment/config server.
- Gunakan authorization pada download cover/draft dan endpoint file.
- Audit tindakan moderator dan finansial.

## 9. Aturan frontend

- Komponen reusable sebelum membuat duplikasi.
- Jangan memakai `any` kecuali ada alasan yang dicatat.
- Semua form memiliki loading, error, validation, dan success state.
- Semua halaman memiliki loading/empty/error state yang relevan.
- Gunakan semantic HTML dan accessible label.
- Jangan membuat layout berbeda dengan logic yang sama hanya karena desktop/mobile.
- Gunakan breakpoint viewport, bukan ukuran fisik perangkat.
- Tailwind digunakan sebagai styling system utama; hindari CSS global yang tidak terkontrol.
- Design tokens digunakan untuk warna, radius, spacing, dan typography.

## 10. Aturan testing

- Unit test untuk value object, status transition, komisi, masa tunggu, minimum withdraw, dan entitlement.
- Feature test untuk auth, ownership, submit review, moderator decision, publish, reader access, dan Library.
- Test negatif wajib: user lain mengakses draft, penulis lain mengedit novel, pengunjung membeli, callback duplikat, dan withdraw di bawah minimum.
- Browser/E2E test untuk alur tulis → submit → approve → baca.
- Jalankan formatter, linter, type check, dan test sebelum merge.

## 11. Aturan Git

- Satu perubahan terfokus per branch/commit jika memungkinkan.
- Commit memakai pesan yang jelas.
- Jangan commit `.env`, secret, upload user, database produksi, atau credential.
- Pull request harus menjelaskan scope, migration, risiko, test, dan screenshot UI jika relevan.
- Jangan mengubah schema tanpa migration dan rollback consideration.

## 12. Prioritas implementasi

1. Fondasi project, auth, role, layout, dan design system.
2. Novel, chapter, draft, editor, dan initial review.
3. Public catalog, reader, Library, bookmark, dan progress.
4. Reports, audit berkala, komentar, notifikasi, dan statistik.
5. Ink, payment, Shop, earnings, refund, dan withdraw.
