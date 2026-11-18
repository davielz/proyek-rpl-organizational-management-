# Tugas Mata Kuliah Rekayasa Perangkat Lunak
- Nama : Davi Firdaus
- NIM : 41257096
- Tema : Management
- Video demonstrasi : [https://youtu.be/jGpw9ajXtA0?si=B1c3hn6jyGRSAab2](https://youtu.be/jGpw9ajXtA0?si=B1c3hn6jyGRSAab2)


# Campus Organization Management System

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-10.11-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-v4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

**Campus Organization Management System** adalah aplikasi manajemen terpadu yang dirancang khusus untuk mengelola operasional, kegiatan, administrasi proposal/LPJ, serta umpan balik (feedback) dan analisis sentimen pada organisasi atau Unit Kegiatan Mahasiswa (UKM) di lingkungan kampus.

Aplikasi ini dibangun menggunakan arsitektur monolitik modern berbasis **Laravel 12**, **Blade**, **Livewire**, **Tailwind CSS v4**, serta didukung integrasi **LLM API (AI)** dan **DomPDF**.

---

## 🚀 Fitur Utama & Ruang Lingkup MVP

1. **Authentication & Multi-Role Access Control**:
   - Sistem login, registrasi, logout, dan manajemen profil.
   - Otorisasi terproteksi penuh pada sisi server (*server-side authorization*) untuk 3 peranan utama:
     - **Admin**: Mengelola pengguna, hak akses (*role*), meninjau proposal, dan melihat analitik global.
     - **Pengurus Organisasi**: Mempublikasikan kegiatan, menyusun proposal/LPJ dengan AI helper, dan memantau ulasan.
     - **Anggota / Peserta**: Melihat katalog kegiatan, mendaftar kegiatan, dan memberikan ulasan & rating.
2. **Manajemen Kegiatan Organisasi (Events)**:
   - Pengelolaan siklus hidup kegiatan (*draft, pending, active, completed, cancelled*).
   - Penjadwalan, lokasi, kuota, serta pendaftaran & absensi peserta.
3. **Proposal & LPJ dengan AI Helper & Export PDF**:
   - Penyusunan draf proposal dan LPJ kegiatan.
   - Bantuan AI generator untuk menyempurnakan latar belakang, tujuan, dan kesimpulan.
   - Fitur ekspor dokumen ke format PDF secara instan.
4. **Feedback Kegiatan & Sentiment Analysis**:
   - Formulir umpan balik interaktif untuk peserta kegiatan.
   - Analisis sentimen otomatis berbasis AI (*Positif, Netral, Negatif*) beserta skor kepercayaan.
5. **Dashboard Organisasi**:
   - Tampilan antarmuka dinamis sesuai role pengguna dengan ringkasan statistik kegiatan.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 12 (PHP 8.5)
- **Database**: MySQL / MariaDB (`campus_org`)
- **Frontend / Styling**: Blade Components, Livewire, Tailwind CSS v4
- **AI Integration**: Laravel HTTP Client & REST API (OpenAI / Gemini / Groq compatible)
- **PDF Export**: DomPDF (`barryvdh/laravel-dompdf`)
- **Testing & Quality Assurance**: PHPUnit, Laravel Pint, Laravel Boost

---

## 🔑 Kredensial Demo Development

Database awal secara otomatis dilengkapi dengan data contoh (*seed data*). Semua akun default menggunakan password: `password`

| Peran (Role) | Email Login | Password | Deskripsi Akses |
|---|---|---|---|
| **Admin** | `admin@campus.ac.id` | `password` | Pengelola global sistem & peranan user |
| **Pengurus** | `pengurus@campus.ac.id` | `password` | Pengurus UKM HMTI (Pengelola Kegiatan) |
| **Anggota 1** | `anggota1@campus.ac.id` | `password` | Anggota terdaftar |
| **Anggota 2** | `anggota2@campus.ac.id` | `password` | Anggota terdaftar |
| **Anggota 3** | `anggota3@campus.ac.id` | `password` | Anggota terdaftar |

---

## 📦 Panduan Instalasi & Jalankan Lokal

### 1. Prasyarat
- PHP >= 8.3 / 8.5
- Composer
- Node.js (v20+) & npm
- MariaDB / MySQL Server

### 2. Langkah Instalasi

```bash
# 1. Clone repository
git clone git@github.com:davielz/organizational-management.git
cd organizational-management

# 2. Install PHP Dependencies
composer install

# 3. Install NPM Dependencies
npm install

# 4. Salin Environment File & Buat Kunci Aplikasi
cp .env.example .env
php artisan key:generate

# 5. Konfigurasi Koneksi Database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=campus_org
# DB_USERNAME=root
# DB_PASSWORD=root

# 6. Jalankan Migrasi & Seeder Database
php artisan migrate:fresh --seed

# 7. Build Aset Frontend
npm run build

# 8. Jalankan Dev Server
php artisan serve
```

Aplikasi dapat diakses di browser melalui URL: `http://localhost:8000`

---

## 🧪 Pengujian & Testing

Jalankan perintah berikut untuk memastikan seluruh pengujian otomatis (*automated test suite*) dan pemformatan kode berjalan bersih:

```bash
# Jalankan PHPUnit Test Suite
php artisan test

# Jalankan Formatter Kode Laravel Pint
vendor/bin/pint --format agent
```

---

## 📑 Dokumentasi Teknis Terkait

Detail rancangan proyek dan skema teknis selengkapnya dapat dilihat pada direktori `docs/`:

- 📄 [docs/project-overview.md](docs/project-overview.md) – Gambaran umum produk, MVP scope, dan tahapan pengembangan.
- 📄 [docs/technical-blueprint.md](docs/technical-blueprint.md) – Blueprint arsitektur teknis, ERD, alur kerja, dan peta rute.
- 📄 [docs/database.md](docs/database.md) – Dokumentasi struktur tabel basis data, indeks, dan relasi Eloquent.

---

&copy; {{ date('Y') }} Campus Organization Management System. Developed with Vibe Coding approach.
