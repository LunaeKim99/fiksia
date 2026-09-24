# Schema — Rancangan Database Platform Web Novel

## 1. Prinsip schema

- Database utama produksi: MySQL.
- SQLite dapat dipakai untuk prototipe lokal, tetapi query dan constraint wajib diuji kembali pada MySQL.
- Gunakan UUID/ULID untuk identifier publik jika diperlukan; jangan mengekspos ID berurutan untuk resource sensitif.
- Semua tabel memiliki `created_at` dan `updated_at`; gunakan `deleted_at` hanya jika soft delete sesuai aturan bisnis.
- Tanggal dan waktu disimpan konsisten dalam UTC; tampilkan ke timezone pengguna.
- Draft, versi terbit, submission, audit, wallet, earnings, dan payment dibuat sebagai data yang dapat diaudit.

## 2. Entitas akun dan akses

### users

- `id`
- `name`
- `email`
- `password`
- `email_verified_at`
- `birth_date` atau mekanisme usia sesuai keputusan privasi
- `status`
- timestamps

### profiles

- `id`
- `user_id` unique
- `username` unique
- `display_name`
- `bio`
- `avatar_path`
- `theme_preference`
- `reading_preferences` JSON
- timestamps

### roles, permissions, model_has_roles

Gunakan package permission teruji atau tabel role/permission internal. Minimal roles: member, moderator, admin. Status penulis berasal dari kemampuan akun, bukan harus role terpisah.

## 3. Entitas katalog dan karya

### novels

- `id`
- `user_id` owner
- `title`
- `slug` unique
- `cover_path`
- `synopsis`
- `status`: draft, initial_review, published, revision_required, suspended, archived, rejected
- `story_status`: ongoing, completed, hiatus
- `initial_review_status`
- `published_at`
- timestamps
- optional `deleted_at`

Index: owner, status, story_status, published_at. Search index/strategy dapat ditambahkan kemudian.

### chapters

- `id`
- `novel_id`
- `chapter_number`
- `title`
- `content` atau reference ke content storage
- `status`: draft, published, hidden, archived
- `is_free`
- `published_at`
- timestamps
- optional `deleted_at`

Unique: `(novel_id, chapter_number)` jika aturan tidak mengizinkan nomor duplikat. Pastikan chapter yang belum terbit tidak ditampilkan publik.

### chapter_revisions

- `id`
- `chapter_id`
- `user_id`
- `content`
- `revision_number`
- `status`
- `submitted_at`
- `approved_at`
- timestamps

Dipakai agar revisi tidak langsung menimpa versi terbit.

### genres, tags, novel_tags

- `genres`: id, name, slug, status.
- `tags`: id, name, slug, status.
- `novel_tags`: novel_id, tag_id; unique pair.

## 4. Library dan komunitas

### follows

- `user_id`
- `novel_id`
- timestamps
- unique pair

### saved_novels

- `user_id`
- `novel_id`
- timestamps
- unique pair

### reading_progress

- `id`
- `user_id`
- `novel_id`
- `chapter_id`
- `position` optional
- `last_read_at`
- timestamps
- unique `(user_id, novel_id)` bila hanya menyimpan satu posisi terakhir

### bookmarks

- `id`
- `user_id`
- `chapter_id`
- `position` optional
- `note` optional
- timestamps

### comments

- `id`
- `user_id`
- `novel_id` nullable
- `chapter_id` nullable
- `parent_id` nullable
- `body`
- `status`: visible, hidden, reported, removed
- timestamps

Validasi memastikan target komentar valid dan user memiliki akses untuk berkomentar.

### notifications

- `id`
- `user_id`
- `type`
- `data` JSON
- `read_at`
- timestamps

## 5. Pemeriksaan, audit, dan laporan

### initial_submissions

- `id`
- `novel_id`
- `submitted_by`
- `status`: submitted, reviewing, approved, revision_required, rejected
- `reviewer_id` nullable
- `review_notes` nullable
- `submitted_at`
- `decided_at` nullable
- timestamps

Submission mereferensikan versi metadata/cover/bab awal yang diajukan agar isi yang dinilai dapat dilacak.

### submission_items

- `id`
- `submission_id`
- `item_type`: cover, synopsis, metadata, chapter
- `chapter_id` nullable
- `snapshot` JSON/text
- `sort_order`
- timestamps

### content_audits

- `id`
- `novel_id`
- `chapter_id` nullable
- `assigned_to` nullable
- `status`: planned, in_review, passed, revision_required, restricted, removed
- `notes` nullable
- `audited_at` nullable
- timestamps

### reports

- `id`
- `reporter_id`
- `target_type`
- `target_id`
- `reason`
- `description`
- `status`: pending, reviewing, resolved, rejected
- `assigned_to` nullable
- `resolution_notes` nullable
- timestamps

### moderation_actions

- `id`
- `moderator_id`
- `target_type`
- `target_id`
- `action`
- `reason`
- `metadata` JSON nullable
- timestamps

## 6. Entitas monetisasi Ink

Monetisasi belum masuk MVP, tetapi schema berikut menjadi rancangan fase lanjut. Nama teknis sengaja generik agar brand `Ink` dapat diganti.

### currency_packages

- `id`
- `name`
- `currency_amount` integer
- `admin_fee` integer
- `total_price` integer
- `is_active`
- `sort_order`
- timestamps

Contoh konfigurasi:

```text
currency_amount = 100000
admin_fee       = 5000
total_price     = 105000
```

```text
currency_amount = 500000
admin_fee       = 10000
total_price     = 510000
```

`total_price` dapat dihitung server-side dan disimpan sebagai snapshot order; jangan percaya total dari browser.

### user_wallets

- `id`
- `user_id` unique
- `currency_balance` integer
- timestamps

Balance adalah cache/summary; sumber kebenaran perubahan tetap `wallet_transactions`.

### wallet_transactions

- `id`
- `wallet_id`
- `user_id`
- `type`: top_up, purchase, refund, adjustment
- `amount` signed integer
- `balance_before`
- `balance_after`
- `reference_type`
- `reference_id`
- `idempotency_key` unique
- `metadata` JSON nullable
- timestamps

### payment_orders

- `id`
- `user_id`
- `package_id`
- `currency_amount`
- `admin_fee`
- `gateway_fee` nullable
- `total_price`
- `provider`
- `provider_order_id` unique nullable
- `status`: pending, paid, failed, expired, refunded
- `paid_at` nullable
- timestamps

### payment_callbacks

- `id`
- `payment_order_id`
- `provider_event_id` unique
- `event_type`
- `payload_hash`
- `processed_at`
- timestamps

Simpan hanya payload yang diperlukan untuk rekonsiliasi; jangan menyimpan data sensitif payment card.

### products

- `id`
- `novel_id` nullable
- `chapter_id` nullable
- `name`
- `price_in_currency`
- `is_active`
- timestamps

### purchases

- `id`
- `user_id`
- `product_id`
- `amount_in_currency`
- `status`
- `wallet_transaction_id`
- timestamps

### entitlements

- `id`
- `user_id`
- `product_id` atau target type/id
- `source_purchase_id`
- `granted_at`
- `revoked_at` nullable
- timestamps

Entitlement menentukan akses baca, bukan saldo wallet saat ini.

## 7. Penghasilan dan withdraw

### earnings_ledger

- `id`
- `author_id`
- `purchase_id` nullable
- `type`: sale, commission, refund, adjustment, release
- `gross_amount`
- `platform_fee`
- `gateway_fee`
- `net_amount`
- `available_at`
- `status`: pending, available, reversed, paid
- `reference_type`
- `reference_id`
- timestamps

Rencana bisnis: komisi platform 5–10% dari penjualan; masa tunggu minimal 7 hari; minimum withdraw Rp50.000. Rumus final harus dikonfigurasi dan divalidasi.

### payout_accounts

- `id`
- `user_id`
- `provider_type`: bank, e_wallet
- `provider_name`
- `account_name`
- `account_identifier_encrypted`
- `account_identifier_masked`
- `is_verified`
- timestamps

### withdrawal_requests

- `id`
- `user_id`
- `payout_account_id`
- `gross_amount`
- `admin_fee`
- `net_amount`
- `status`: requested, approved, processing, paid, failed, returned, rejected
- `provider_reference` nullable
- `failure_reason` nullable
- `requested_at`
- `processed_at` nullable
- timestamps

Validasi: saldo tersedia minimal Rp50.000, pendapatan telah melewati 7 hari, payout account terverifikasi, dan tidak ada withdrawal duplikat yang masih berjalan.

### refunds dan financial_adjustments

- Refund: order/purchase, nominal, alasan, status, reviewer, timestamps.
- Financial adjustment: author, reference, debit/credit, amount, reason, actor, audit metadata.

## 8. Relasi utama

```text
User 1──N Novel
Novel 1──N Chapter
Chapter 1──N ChapterRevision
Novel N──N Tag
User N──N Novel (Follow)
User N──N Novel (SavedNovel)
User 1──N ReadingProgress
User 1──N Bookmark
User 1──N Comment
Novel/Chapter 1──N Report
Novel 1──N InitialSubmission
Novel/Chapter 1──N ContentAudit
User 1──1 UserWallet
UserWallet 1──N WalletTransaction
User 1──N PaymentOrder
PaymentOrder 1──N PaymentCallback
User 1──N Purchase
Purchase 1──N Entitlement
Purchase 1──N EarningsLedger
User 1──N WithdrawalRequest
User 1──N PayoutAccount
```

## 9. Aturan integritas

- Penulis tidak boleh mengubah Novel/Chapter milik user lain.
- Draft dan pending submission tidak boleh dikembalikan oleh query publik.
- Bab hanya dapat dibuka jika status published dan akses terpenuhi.
- Wallet transaction tidak boleh diubah sembarangan setelah posted; gunakan reversal/adjustment.
- Callback pembayaran yang sama tidak boleh diproses dua kali.
- Satu purchase tidak boleh memberikan entitlement ganda secara tidak sengaja.
- Earnings tidak boleh langsung available sebelum `available_at` terpenuhi.
- Withdrawal di bawah minimum atau sebelum masa tunggu harus ditolak.
- Semua penghapusan/penurunan konten yang memengaruhi akses pembeli harus memiliki audit dan kebijakan refund.
