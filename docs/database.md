# Database Documentation: Campus Organization Management System

Dokumentasi ini menjelaskan secara rinci struktur basis data, skema tabel, relasi antar-model Eloquent, serta data awal (seed data) yang tersedia untuk pengembangan aplikasi.

---

## 1. Overview Database

- **Database Management System**: MySQL / MariaDB (Database: `campus_org`, Test DB: `campus_org_test`)
- **Collation**: `utf8mb4_unicode_ci`
- **ORM**: Laravel Eloquent
- **Total Tabel Utama**: 7 Tabel (`users`, `organizations`, `organization_user`, `events`, `event_registrations`, `documents`, `event_feedbacks`)

---

## 2. Skema & Struktur Tabel

### A. Tabel `users`
Menyimpan identitas pengguna aplikasi beserta peran globalnya (*role*).

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `name` | `VARCHAR(255)` | No | - | Nama lengkap user |
| `email` | `VARCHAR(255)` | No | - | **UNIQUE** Email login |
| `email_verified_at` | `TIMESTAMP` | Yes | `NULL` | Waktu verifikasi email |
| `password` | `VARCHAR(255)` | No | - | Hash password |
| `role` | `ENUM('admin', 'pengurus', 'anggota')` | No | `'anggota'` | Peran pengguna |
| `phone` | `VARCHAR(255)` | Yes | `NULL` | Nomor HP/WhatsApp |
| `avatar` | `VARCHAR(255)` | Yes | `NULL` | Path foto profil |
| `remember_token` | `VARCHAR(100)` | Yes | `NULL` | Token ingat saya |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diperbarui |

---

### B. Tabel `organizations`
Menyimpan master data UKM / Organisasi Mahasiswa Kampus.

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `name` | `VARCHAR(255)` | No | - | Nama UKM/Organisasi |
| `code` | `VARCHAR(255)` | No | - | **UNIQUE** Kode (contoh: HMTI, BEM) |
| `description` | `TEXT` | Yes | `NULL` | Deskripsi profil UKM |
| `logo` | `VARCHAR(255)` | Yes | `NULL` | Path gambar logo |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diperbarui |

---

### C. Tabel Pivot `organization_user`
Menghubungkan user dengan organisasi beserta perannya dalam organisasi tersebut.

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `organization_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `organizations(id)` ON DELETE CASCADE |
| `user_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `users(id)` ON DELETE CASCADE |
| `role_in_org` | `ENUM('pengurus', 'anggota')` | No | `'anggota'` | Peran dalam organisasi |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Tanggal bergabung |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diubah |

- **Indexes & Unique**:
  - `UNIQUE(organization_id, user_id)` (mencegah keanggotaan ganda pada org yang sama)

---

### D. Tabel `events`
Menyimpan data kegiatan organisasi mahasiswa.

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `organization_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `organizations(id)` ON DELETE CASCADE |
| `created_by` | `BIGINT UNSIGNED` | No | - | **FK** -> `users(id)` ON DELETE CASCADE |
| `title` | `VARCHAR(255)` | No | - | Judul acara |
| `slug` | `VARCHAR(255)` | No | - | **UNIQUE** URL slug |
| `description` | `TEXT` | No | - | Rincian kegiatan |
| `location` | `VARCHAR(255)` | No | - | Lokasi / Link Zoom |
| `start_time` | `DATETIME` | No | - | Waktu mulai |
| `end_time` | `DATETIME` | No | - | Waktu selesai |
| `quota` | `INT UNSIGNED` | No | `0` | Kuota peserta |
| `status` | `ENUM('draft', 'pending', 'active', 'completed', 'cancelled')` | No | `'draft'` | **INDEX** Status acara |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diubah |

- **Indexes**:
  - `INDEX(organization_id, status)`

---

### E. Tabel `event_registrations`
Menyimpan pendaftaran dan kehadiran peserta pada kegiatan.

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `event_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `events(id)` ON DELETE CASCADE |
| `user_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `users(id)` ON DELETE CASCADE |
| `status` | `ENUM('registered', 'attended', 'cancelled')` | No | `'registered'` | **INDEX** Status kehadiran |
| `registered_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | Tanggal pendaftaran |
| `attended_at` | `TIMESTAMP` | Yes | `NULL` | Waktu presensi hadir |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diubah |

- **Indexes & Unique**:
  - `UNIQUE(event_id, user_id)`

---

### F. Tabel `documents` (Proposal & LPJ)
Menyimpan dokumen proposal pengajuan dan Laporan Pertanggungjawaban (LPJ).

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `event_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `events(id)` ON DELETE CASCADE |
| `submitted_by` | `BIGINT UNSIGNED` | No | - | **FK** -> `users(id)` ON DELETE CASCADE |
| `approved_by` | `BIGINT UNSIGNED` | Yes | `NULL` | **FK** -> `users(id)` ON DELETE SET NULL |
| `type` | `ENUM('proposal', 'lpj')` | No | - | **INDEX** Jenis dokumen |
| `title` | `VARCHAR(255)` | No | - | Judul dokumen |
| `background` | `LONGTEXT` | Yes | `NULL` | Latar belakang |
| `objectives` | `LONGTEXT` | Yes | `NULL` | Tujuan & sasaran |
| `budget_plan` | `LONGTEXT` | Yes | `NULL` | Rencana/realisasi anggaran |
| `summary_content` | `LONGTEXT` | Yes | `NULL` | Kesimpulan/laporan |
| `ai_generated_draft`| `LONGTEXT` | Yes | `NULL` | Teks rekomendasi AI |
| `status` | `ENUM('draft', 'submitted', 'approved', 'revision_requested', 'rejected')` | No | `'draft'` | **INDEX** Status approval |
| `notes` | `TEXT` | Yes | `NULL` | Catatan revisi/penolakan |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diubah |

- **Indexes**:
  - `INDEX(event_id, type)`

---

### G. Tabel `event_feedbacks`
Menyimpan ulasan peserta dan hasil analisis sentimen AI.

| Kolom | Tipe Data | Nullable | Default | Keterangan & Constraint |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Auto Increment | **PRIMARY KEY** |
| `event_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `events(id)` ON DELETE CASCADE |
| `user_id` | `BIGINT UNSIGNED` | No | - | **FK** -> `users(id)` ON DELETE CASCADE |
| `rating` | `TINYINT UNSIGNED` | No | - | Rating 1 s/d 5 |
| `comment` | `TEXT` | No | - | Teks ulasan peserta |
| `sentiment` | `ENUM('positive', 'neutral', 'negative')` | No | `'neutral'` | **INDEX** Hasil Analisis AI |
| `sentiment_score` | `DECIMAL(4,2)` | No | `0.00` | Skor probabilitas sentimen |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Waktu diubah |

- **Indexes & Unique**:
  - `UNIQUE(event_id, user_id)`

---

## 3. Pemetaan Relasi Eloquent Models

- **`User`**:
  - `belongsToMany(Organization::class)` via `organization_user` (Pivot: `role_in_org`).
  - `hasMany(Event::class, 'created_by')`.
  - `belongsToMany(Event::class, 'event_registrations')` (Pivot: `status`, `registered_at`, `attended_at`).
  - `hasMany(Document::class, 'submitted_by')`.
  - `hasMany(Document::class, 'approved_by')`.
  - `hasMany(EventFeedback::class)`.

- **`Organization`**:
  - `belongsToMany(User::class)` via `organization_user`.
  - `hasMany(Event::class)`.

- **`Event`**:
  - `belongsTo(Organization::class)`.
  - `belongsTo(User::class, 'created_by')`.
  - `hasMany(EventRegistration::class)`.
  - `belongsToMany(User::class, 'event_registrations')`.
  - `hasMany(Document::class)`.
  - `hasOne(Document::class)` -> `proposal()`.
  - `hasOne(Document::class)` -> `lpj()`.
  - `hasMany(EventFeedback::class)`.

- **`EventRegistration`**:
  - `belongsTo(Event::class)`.
  - `belongsTo(User::class)`.

- **`Document`**:
  - `belongsTo(Event::class)`.
  - `belongsTo(User::class, 'submitted_by')`.
  - `belongsTo(User::class, 'approved_by')`.

- **`EventFeedback`**:
  - `belongsTo(Event::class)`.
  - `belongsTo(User::class)`.

---

## 4. Akun Default Seeder (Development Credentials)

Semua akun default menggunakan password: `password`

| Peran (Role) | Email Login | Password | Keterangan |
|---|---|---|---|
| **Admin** | `admin@campus.ac.id` | `password` | Pengelola global sistem & approval proposal |
| **Pengurus** | `pengurus@campus.ac.id` | `password` | Pengurus UKM HMTI, pembuat event & proposal |
| **Anggota 1** | `anggota1@campus.ac.id` | `password` | Anggota HMTI (Status: Attended Event 1) |
| **Anggota 2** | `anggota2@campus.ac.id` | `password` | Anggota HMTI (Status: Attended Event 1) |
| **Anggota 3** | `anggota3@campus.ac.id` | `password` | Anggota HMTI (Status: Registered Event 1) |

---
*Status Lapisan Database: Siap untuk integrasi controller dan antarmuka.*
