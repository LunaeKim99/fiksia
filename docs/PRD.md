# PRD — Platform Web Novel Komunitas

## 1. Ringkasan produk

Platform web novel komunitas untuk pengguna berusia 16 tahun ke atas. Pengguna tanpa akun dapat menemukan dan membaca bab gratis. Pengguna terdaftar dapat menyimpan karya, mengikuti penulis, membuat bookmark, melanjutkan progres, berkomentar sesuai kebijakan, serta menjadi penulis.

Penulis mengirim cover, sinopsis, metadata, dan beberapa bab awal untuk pemeriksaan. Setelah lolos, penulis dapat menerbitkan bab lanjutan; platform menjalankan audit berkala dan menerima laporan komunitas. Monetisasi menggunakan currency internal bernama Ink direncanakan setelah MVP gratis stabil.

## 2. Tujuan MVP

- Membuktikan alur utama: tulis → ajukan → review awal → terbit → baca.
- Menyediakan pengalaman membaca yang nyaman dan responsif.
- Membuat Studio Penulis yang benar-benar dapat digunakan.
- Menyediakan dasar moderasi, audit log, dan laporan.
- Menghindari implementasi pembayaran palsu sebelum aturan dan provider siap.

## 3. Non-goals MVP

- Payment gateway dan top-up Ink.
- Pembelian novel/bab.
- Withdraw dan pembayaran penulis.
- Komisi platform dan refund otomatis.
- Rekomendasi AI kompleks.
- Aplikasi mobile native.
- Moderasi otomatis berbasis AI.

## 4. Persona

### Pengunjung

Ingin membaca novel tanpa proses pendaftaran. Dapat menjelajah dan membaca bab gratis, tetapi tidak dapat save/bookmark/follow, membeli Ink, berkomentar, atau menulis.

### Pembaca terdaftar

Ingin menyimpan bacaan, melanjutkan progres, mengikuti novel, dan berinteraksi dengan komunitas.

### Penulis

Ingin membuat novel, mengatur bab, mengetahui status review, menerima feedback, memahami statistik pembaca, dan kelak memperoleh pendapatan.

### Moderator

Ingin memeriksa pengajuan awal, melakukan audit berkala, menangani laporan, dan menyimpan keputusan yang konsisten.

## 5. User stories MVP

### Pengunjung

- Sebagai pengunjung, saya dapat membuka Home dan Explore.
- Sebagai pengunjung, saya dapat mencari novel berdasarkan judul, penulis, genre, atau tag.
- Sebagai pengunjung, saya dapat membaca bab gratis yang telah terbit.
- Sebagai pengunjung, saya mendapat ajakan login ketika mencoba fitur personal.

### Pembaca

- Sebagai pembaca, saya dapat mendaftar dan login.
- Sebagai pembaca, saya dapat follow dan menyimpan novel.
- Sebagai pembaca, saya dapat membuat bookmark.
- Sebagai pembaca, saya dapat melanjutkan dari progres terakhir.
- Sebagai pembaca, saya dapat mengubah tema dan pengaturan reader.
- Sebagai pembaca, saya dapat memberikan komentar jika fitur komentar sudah diaktifkan.

### Penulis

- Sebagai penulis, saya dapat membuat novel.
- Sebagai penulis, saya dapat mengunggah cover yang valid.
- Sebagai penulis, saya dapat mengisi sinopsis, genre, tag, dan status cerita.
- Sebagai penulis, saya dapat membuat dan mengedit draft bab.
- Sebagai penulis, saya dapat melihat preview dan status autosave.
- Sebagai penulis, saya dapat mengirim cover, sinopsis, metadata, dan bab awal untuk review.
- Sebagai penulis, saya dapat melihat keputusan moderator dan catatan revisi.
- Sebagai penulis, saya dapat menerbitkan bab lanjutan setelah novel lolos pemeriksaan awal.
- Sebagai penulis, saya dapat melihat statistik dasar per novel dan keseluruhan jika data sudah tersedia.

### Moderator/admin

- Sebagai moderator, saya dapat melihat antrean pengajuan awal.
- Sebagai moderator, saya dapat memeriksa cover, sinopsis, metadata, dan beberapa bab awal.
- Sebagai moderator, saya dapat approve, request revision, atau reject dengan catatan.
- Sebagai moderator, saya dapat melihat antrean audit dan laporan.
- Sebagai admin, saya dapat mengelola genre, tag, pengguna, role, serta audit log.

## 6. Fitur dan acceptance criteria

### Auth dan profil

- Register, login, logout, reset password, dan verifikasi email dapat diaktifkan sesuai prioritas.
- Role anggota, penulis, moderator, dan admin menggunakan authorization server-side.
- Profil publik menampilkan nama, bio, avatar, dan karya terbit.
- Data pribadi tidak terbuka ke publik.

### Novel dan bab

- Novel memiliki judul, slug, cover, sinopsis, genre, tag, status cerita, pemilik, dan status publikasi.
- Penulis hanya dapat mengedit novel miliknya.
- Bab memiliki judul, urutan, konten, status draft/terbit, dan waktu terbit.
- Draft tidak tampil di halaman publik.
- Urutan bab tidak boleh ganda dalam novel yang sama.
- Perubahan konten publik tidak boleh merusak versi yang sedang dibaca tanpa aturan versi.

### Pemeriksaan awal dan audit

- Pengajuan awal mencakup cover, sinopsis, metadata, dan jumlah bab awal yang ditentukan admin.
- Status: draft, submitted, reviewing, approved, revision_requested, rejected.
- Approval menerbitkan karya/bab awal sesuai keputusan.
- Bab lanjutan dapat diterbitkan tanpa review satu per satu setelah approval awal.
- Audit berkala dibuat sebagai tugas moderator dan menyimpan hasil/tindakan.
- Laporan dapat diprioritaskan di luar jadwal audit.

### Reader

- Hanya novel/bab yang terbit dan dapat diakses yang dapat dibaca.
- Reader memiliki navigasi bab, progres, bookmark untuk anggota, pengaturan tampilan, dan komentar bila aktif.
- Bab gratis dapat menampilkan promosi internal 15 detik di transisi antar-bab setelah fitur promosi diaktifkan.
- Reader tetap dapat dibaca oleh pengunjung tanpa akun untuk bab gratis.

### Library

- Tab/filter: mengikuti, disimpan, sedang dibaca, selesai.
- Data Library membutuhkan akun.
- Progres tersimpan per pengguna dan novel/bab.

### Studio Penulis

- Dashboard menampilkan novel, draft, pengajuan, hasil audit, dan ringkasan statistik.
- Editor memiliki autosave indicator, preview, save draft, dan submit/publish berdasarkan status.
- Form memberi error yang dapat dipahami.
- Penulis mendapat konfirmasi sebelum publish atau submit.

### Monetisasi fase lanjut

- Paket Ink memiliki nominal Ink, fee admin, total, dan status aktif.
- Contoh: Rp100.000 Ink + Rp5.000 fee = Rp105.000; Rp500.000 Ink + Rp10.000 fee = Rp510.000.
- Top-up hanya dikreditkan setelah callback payment gateway diverifikasi server-side.
- Akses pembelian disimpan sebagai entitlement.
- Platform merencanakan komisi 5–10% dari penjualan.
- Pendapatan penulis menunggu minimal 7 hari dan withdraw minimal Rp50.000.
- Biaya pencairan bank/e-wallet ditampilkan terpisah.
- Semua aturan pajak, pembayaran, currency, refund, dan payout harus divalidasi sebelum rilis.

## 7. Status dan aturan konten

### Status novel

```text
DRAFT
INITIAL_REVIEW
PUBLISHED
REVISION_REQUIRED
SUSPENDED
ARCHIVED
REJECTED
```

### Status bab

```text
DRAFT
PUBLISHED
HIDDEN
ARCHIVED
```

### Status audit

```text
PLANNED
IN_REVIEW
PASSED
REVISION_REQUIRED
RESTRICTED
REMOVED
```

## 8. Navigasi MVP

- Mobile: Home, Explore, Library, Studio, Account pada bottom navigation.
- Tablet: bottom navigation atau navigation rail.
- Desktop: top bar + sidebar/navigation rail.
- Reader memakai layout fokus.
- Wallet/Shop belum muncul sebagai fitur aktif di navigasi MVP.

## 9. Non-functional requirements

- Authorization server-side untuk semua data privat.
- Validasi upload cover di server: extension allowlist, MIME/content validation, ukuran maksimum, nama file sistem, dan storage terisolasi.
- Rate limit untuk login, komentar, submit, report, dan endpoint sensitif.
- Audit log untuk tindakan moderasi dan perubahan finansial.
- Error message tidak membocorkan secret atau detail database.
- Query daftar novel, bab, dan Library diberi index/pagination.
- Basic SEO untuk halaman novel publik; draft/pending tidak diindeks.
- Responsive dan usable pada mobile, tablet, desktop.
- Dukungan Light/Dark/System.
- Backup database dan file upload sebelum peluncuran komunitas.

## 10. Backlog prioritas

### P0 — wajib MVP

- Setup project dan CI/lint/format.
- Auth dan role dasar.
- Design system/layout responsive.
- Database novel/chapter/genre/tag.
- CRUD novel dan chapter.
- Draft, preview, autosave dasar.
- Initial review dan moderator dashboard.
- Publishing dan halaman publik.
- Explore/search/detail/reader.
- Library, follow, bookmark, reading progress.
- Policy, validation, audit log minimum.

### P1 — setelah alur utama stabil

- Komentar dan balasan.
- Notifikasi.
- Reports.
- Audit berkala terjadwal.
- Statistik pembaca.
- Rekomendasi berbasis aturan.
- Promosi internal antar-bab.
- Redis/queue.

### P2 — monetisasi

- Paket Ink dan checkout.
- Payment gateway.
- Wallet ledger dan entitlement.
- Pembelian akses.
- Earnings ledger dan komisi.
- Withdraw dan payout.
- Refund/adjustment.
- Shop.

## 11. Risiko

| Risiko                            | Mitigasi                                                                 |
| --------------------------------- | ------------------------------------------------------------------------ |
| Konten komunitas melanggar aturan | Initial review, audit berkala, report, takedown, audit log.              |
| Kehilangan draft penulis          | Autosave, versi draft, export/backup.                                    |
| Saldo Ink tidak konsisten         | Ledger immutable, idempotency, transaksi database, callback server-side. |
| Akses karya terbeli hilang        | Entitlement terpisah dari wallet; kebijakan refund dan penarikan karya.  |
| Beban moderator meningkat         | Audit berbasis risiko, prioritas laporan, dashboard antrean.             |
| Konflik keamanan upload           | Validasi file server-side, storage terisolasi, batas ukuran.             |
| Perubahan nama currency           | Nama brand hanya di UI/config; tabel teknis generik.                     |

## 12. Definition of Done MVP

- Alur tulis → submit → review → publish → baca dapat diselesaikan tanpa workaround manual.
- Pengunjung dapat membaca karya publik gratis.
- Anggota dapat menyimpan dan melanjutkan bacaan.
- Penulis dapat mengelola draft dan melihat keputusan review.
- Moderator memiliki antrean dan audit trail.
- Tidak ada fungsi pembayaran/Ink palsu yang terlihat aktif.
- Test untuk use case penting dan authorization tersedia.
- Error, loading, empty, dan success state dibuat.
