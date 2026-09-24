# Design — Platform Web Novel Komunitas

## 1. Arah visual

Platform menggunakan Tailwind CSS dengan design system kustom yang terinspirasi Material Design 3. Materialize CSS tidak digunakan sebagai framework utama.

Tujuan visual:

- Fokus pada sampul, judul, sinopsis, dan keterbacaan novel.
- Modern, nyaman, tidak terlalu ramai.
- Mendukung Light, Dark, dan System theme.
- Responsive untuk ponsel, tablet, dan desktop.
- Identitas visual memiliki nuansa literasi, tinta, halaman, dan komunitas.

## 2. Design tokens

Gunakan CSS variables sebagai sumber warna agar tema mudah diubah.

```css
:root {
    --color-primary: #7c3aed;
    --color-on-primary: #ffffff;
    --color-secondary: #a855f7;
    --color-surface: #fffafc;
    --color-surface-variant: #f3edf7;
    --color-outline: #79747e;
    --color-on-surface: #1d1b20;
    --color-error: #ba1a1a;
}

.dark {
    --color-primary: #d0bcff;
    --color-on-primary: #381e72;
    --color-secondary: #e8b7ff;
    --color-surface: #141218;
    --color-surface-variant: #49454f;
    --color-outline: #938f99;
    --color-on-surface: #e6e1e5;
    --color-error: #ffb4ab;
}
```

Nilai final harus disesuaikan dengan identitas brand setelah wireframe dan accessibility check.

## 3. Navigasi responsif

Gunakan lebar viewport sebagai dasar layout.

| Lebar      | Navigasi                                                                  |
| ---------- | ------------------------------------------------------------------------- |
| < 600 px   | Top app bar + bottom navigation: Home, Explore, Library, Studio, Account. |
| 600–839 px | Top bar + bottom bar atau navigation rail ringkas sesuai hasil pengujian. |
| ≥ 840 px   | Top bar + desktop sidebar/navigation rail.                                |

- Search dan notification terlihat dari top bar.
- Settings berada di Account.
- Wallet dan Shop berada di Account/menu tambahan hingga monetisasi aktif.
- Reader menyembunyikan navigasi global saat mode fokus.
- Studio memakai layout penuh pada mobile dan sidebar internal pada desktop.

## 4. Halaman utama

### Home

- Sapaan singkat.
- Continue reading.
- Update novel yang diikuti.
- Novel terbaru.
- Rekomendasi.
- Empty state: ajakan membaca Explore atau menerbitkan cerita pertama.

### Explore/Search

- Search field.
- Genre dan tag.
- Status: ongoing, completed, hiatus.
- Sort: terbaru, populer, paling banyak disimpan.
- Filter pada mobile menggunakan bottom sheet; desktop menggunakan panel/chips.

### Detail novel

- Cover.
- Judul dan penulis.
- Status novel.
- Genre dan tag.
- Sinopsis.
- Tombol mulai baca, lanjut baca, ikuti, simpan.
- Daftar bab dengan status gratis/terkunci bila monetisasi aktif.
- Ringkasan statistik publik dan komentar/ulasan jika fitur tersedia.

## 5. Reader

Reader harus menjadi area paling nyaman dalam produk.

- Mode fokus dan tombol kembali yang jelas.
- Navigasi bab sebelumnya/berikutnya.
- Daftar bab melalui drawer/sheet.
- Ukuran teks, jenis font yang terbatas, line height, alignment, dan tema baca.
- Bookmark dan progres.
- Komentar per bab di bawah konten atau panel terpisah.
- Bab gratis dapat menampilkan promosi novel internal selama 15 detik di transisi antar-bab, bukan di tengah teks.
- Bab terkunci menampilkan harga Ink dan akses pembelian ketika monetisasi aktif.
- Jangan menghilangkan isi karena font diperbesar; uji sampai 200% text resize.

## 6. Studio Penulis

### Dashboard

Tampilkan hal yang perlu dilakukan terlebih dahulu:

- Novel aktif.
- Draft bab.
- Pengajuan awal menunggu review.
- Permintaan revisi.
- Hasil audit terakhir.
- Ringkasan pembaca dan pendapatan jika aktif.

### Editor

- Judul bab.
- Rich text editor.
- Autosave indicator.
- Draft/preview toggle.
- Word count.
- Save draft.
- Submit initial review atau publish chapter lanjutan sesuai status novel.
- Confirm dialog sebelum publish.
- Warning ketika meninggalkan halaman dengan perubahan belum tersimpan.

## 7. Moderation UI

- Queue untuk pengajuan awal.
- Preview cover, sinopsis, metadata, dan beberapa bab awal dalam satu workspace.
- Status timeline.
- Tombol approve, request revision, reject.
- Catatan reviewer wajib saat request revision/reject.
- Queue audit berkala.
- Queue reports.
- Audit trail visible bagi admin.

## 8. Wallet dan Ink

Wallet/Shop dikembangkan setelah MVP komunitas.

### Checkout

Tampilkan angka terpisah:

```text
Ink:          Rp100.000
Biaya admin:  Rp5.000
Total:        Rp105.000
```

Contoh lain:

```text
Ink:          Rp500.000
Biaya admin:  Rp10.000
Total:        Rp510.000
```

- Jangan menyebut fee sebagai diskon.
- Jelaskan bahwa biaya gateway dapat berbeda berdasarkan metode pembayaran bila relevan.
- Tampilkan status pembayaran yang jelas.
- Jangan mengkreditkan Ink sebelum callback server tervalidasi.
- Saldo Ink pembaca tidak sama dengan saldo penghasilan penulis.

### Withdraw

- Saldo tertunda.
- Saldo tersedia.
- Minimum Rp50.000.
- Masa tunggu minimal 7 hari.
- Tujuan rekening/e-wallet tersamarkan.
- Fee pencairan.
- Nominal bersih.
- Status pending, processing, success, failed, returned.

## 9. Komponen wajib

- TopAppBar.
- BottomNavigation.
- DesktopSidebar.
- NavigationRail.
- NovelCard.
- ChapterList.
- GenreChip.
- StatusBadge.
- EmptyState.
- SearchField.
- FilterSheet.
- ReaderSettingsSheet.
- EditorToolbar.
- AutosaveIndicator.
- SubmissionTimeline.
- WalletBalanceCard.
- InkPackageCard.
- PaymentSummary.
- TransactionHistory.
- Dialog, Toast, LoadingState.

## 10. Aksesibilitas dan UX

- Semua kontrol memiliki label yang jelas.
- Fokus keyboard harus terlihat.
- Jangan hanya memakai warna untuk menunjukkan status.
- Kontras teks dan background harus memadai.
- Reader mendukung perbesaran teks tanpa kehilangan fungsi.
- Tombol mobile cukup besar untuk disentuh.
- Loading, empty, error, dan success state wajib dibuat.
- Form menampilkan error dekat dengan field terkait.
- Autosave dan status jaringan harus terlihat.
- Bahasa antarmuka Indonesia dengan istilah konsisten: Novel, Bab, Draft, Terbit, Pengajuan, Audit, Ink, Biaya admin, dan Withdraw.
