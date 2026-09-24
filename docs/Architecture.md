# Architecture — Platform Web Novel Komunitas

## 1. Tujuan

Platform web novel komunitas untuk pengguna 16+ yang dapat membaca, menulis, menerbitkan, mengelola karya, mengikuti penulis, dan kelak membeli akses novel menggunakan currency internal bernama Ink.

Tahap awal menggunakan **modular monolith** dengan Clean Architecture:

```text
Laravel + React + Inertia.js + TypeScript + Tailwind CSS + MySQL + Vite
```

Monolith dipilih agar pengembangan MVP tetap sederhana, tetapi batas antar-layer dibuat jelas sehingga fitur dapat dikembangkan tanpa menumpuk logika di controller atau komponen UI.

## 2. Prinsip Clean Architecture

Dependensi harus mengarah ke dalam:

```text
Presentation → Application → Domain
Infrastructure → Application/Domain melalui interface
```

- **Domain:** aturan bisnis inti dan entity; tidak bergantung pada Laravel, React, database, atau payment gateway.
- **Application:** use case bisnis; mengatur alur kerja menggunakan entity dan interface repository/service.
- **Infrastructure:** Eloquent model, repository implementation, storage, notification channel, payment gateway adapter, dan integrasi eksternal.
- **Presentation:** Laravel controller/Inertia page, React component, form, route, dan serializer/resource.

## 3. Struktur backend

```text
app/
├── Domain/
│   ├── Novel/
│   │   ├── Entities/
│   │   ├── ValueObjects/
│   │   ├── Enums/
│   │   ├── Contracts/
│   │   └── Exceptions/
│   ├── Chapter/
│   ├── Moderation/
│   ├── Reader/
│   ├── Community/
│   ├── Wallet/
│   └── Shared/
│
├── Application/
│   ├── Novel/
│   │   ├── Commands/
│   │   ├── Queries/
│   │   └── DTOs/
│   ├── Chapter/
│   ├── Moderation/
│   ├── Reader/
│   ├── Community/
│   └── Wallet/
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   ├── Repositories/
│   │   └── Migrations/
│   ├── Storage/
│   ├── Notifications/
│   ├── Payments/
│   └── Cache/
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
│
├── Policies/
├── Jobs/
├── Providers/
└── Support/
```

## 4. Struktur frontend

```text
resources/js/
├── Pages/
│   ├── Home/
│   ├── Explore/
│   ├── Novel/
│   ├── Reader/
│   ├── Library/
│   ├── Studio/
│   ├── Moderation/
│   ├── Wallet/
│   ├── Account/
│   └── Settings/
├── Components/
│   ├── Navigation/
│   ├── Novel/
│   ├── Reader/
│   ├── Studio/
│   ├── Wallet/
│   └── UI/
├── Layouts/
│   ├── PublicLayout.tsx
│   ├── ReaderLayout.tsx
│   ├── WriterLayout.tsx
│   └── AdminLayout.tsx
├── Features/
├── Hooks/
├── Types/
├── Lib/
└── app.tsx
```

UI tidak boleh berisi aturan bisnis seperti menentukan apakah bab boleh terbit, menghitung komisi, atau menambah saldo. UI hanya mengirim intent/form dan menampilkan hasil use case.

## 5. Batas modul inti

- **Novel:** metadata, cover, genre, tag, status cerita, kepemilikan.
- **Chapter:** draft, revisi, urutan, penerbitan, reader content.
- **Moderation:** pemeriksaan awal, audit berkala, laporan, keputusan, audit log.
- **Reader:** progres, bookmark, akses baca, promosi antar-bab.
- **Community:** follow, save, komentar, notifikasi.
- **Wallet:** Ink, paket, order, wallet ledger, entitlement.
- **Earnings:** pendapatan penulis, komisi, masa tunggu, withdraw.

Wallet dan Earnings boleh disiapkan sebagai modul/folder, tetapi jangan diaktifkan sebelum fitur monetisasi siap.

## 6. Alur arsitektur contoh

```text
React Form
  → Inertia Request
  → Laravel Controller
  → Form Request validation
  → Application Use Case
  → Domain Entity/Policy
  → Repository Contract
  → Eloquent Repository
  → Database
  → DTO/Resource
  → Inertia Response
  → React Page
```

Contoh submit pengajuan awal:

```text
SubmitInitialReviewController
  → SubmitInitialReviewRequest
  → SubmitInitialReviewUseCase
  → NovelPolicy + Submission entity
  → InitialSubmissionRepository
  → database
  → notification job
```

## 7. Aturan desain

- Controller tipis; jangan menaruh alur bisnis kompleks di controller.
- Model Eloquent tidak menjadi satu-satunya tempat seluruh aturan bisnis.
- Gunakan interface repository atau port pada Domain/Application; implementasi Eloquent berada di Infrastructure.
- Gunakan DTO untuk input/output use case.
- Gunakan enum untuk status novel, chapter, submission, audit, order, earnings, dan withdrawal.
- Gunakan Policy untuk authorization dan gunakan validasi request untuk bentuk data.
- Semua operasi uang harus memakai integer dalam satuan rupiah/Ink terkecil, bukan floating point.
- Semua operasi pembayaran, pengkreditan Ink, pembelian akses, dan withdraw harus idempoten.
- Jangan mengurangi saldo hanya berdasarkan data dari browser; validasi di server.
- Jangan menyimpan secret payment gateway di frontend.
- Setiap tindakan moderasi dan perubahan finansial wajib memiliki audit log.
- Feature test untuk use case penting; unit test untuk domain rule; browser test untuk alur utama.

## 8. Deployment awal

- Local: Laravel, Vite, MySQL/SQLite, storage lokal.
- Production awal: PHP runtime, MySQL, object storage, queue worker, scheduler, dan HTTPS.
- Redis ditambahkan ketika notifikasi, audit, statistik, atau queue membutuhkan skalabilitas.
- Backup database dan file upload wajib dirancang sebelum platform menerima karya komunitas.
