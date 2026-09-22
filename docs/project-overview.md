# Campus Organization Management System

## Product Overview
Campus Organization Management System adalah aplikasi manajemen terpadu yang dirancang khusus untuk mengelola kegiatan, operasional, administrasi proposal/LPJ, serta umpan balik (feedback) dan analisis sentimen pada organisasi atau Unit Kegiatan Mahasiswa (UKM) di lingkungan kampus.

Aplikasi ini membantu pengurus dan anggota organisasi mengelola siklus hidup kegiatan kampus secara efisien, transparan, dan terukur, dibantu dengan integrasi AI untuk penyusunan dokumen proposal/LPJ serta analisis sentimen publik.

---

## Target Users & Roles
1. **Admin**
   - Mengelola master data user, role, permission, dan organisasi/UKM.
   - Memiliki akses penuh ke seluruh modul sistem dan konfigurasi aplikasi.
2. **Pengurus Organisasi**
   - Membuat, merencanakan, dan mengelola kegiatan organisasi.
   - Mengajukan proposal kegiatan dan LPJ (Laporan Pertanggungjawaban) dengan bantuan AI.
   - Memantau feedback peserta dan statistik sentimen kegiatan.
3. **Anggota Organisasi / Peserta**
   - Melihat daftar kegiatan organisasi yang akan datang dan yang telah selesai.
   - Mendaftar/mengikuti kegiatan.
   - Memberikan feedback dan penilaian atas kegiatan yang diikuti.

---

## MVP Scope (Ruang Lingkup MVP)
1. **Authentication & Role Management**
   - Sistem login, registrasi, logout, reset password.
   - Multi-role access control (Admin, Pengurus, Anggota).
2. **Manajemen Kegiatan (Event Management)**
   - Create, Read, Update, Delete (CRUD) kegiatan organisasi.
   - Status kegiatan (Draft, Pending Approval, Active, Completed, Cancelled).
   - Penjadwalan dan detail lokasi/link kegiatan.
3. **Manajemen Proposal & LPJ dengan Integrasi AI**
   - Penyusunan draft proposal dan LPJ kegiatan.
   - Fitur AI Assistant (via LLM API) untuk membantu membuat/menyempurnakan latar belakang, tujuan, rincian acara, dan simpulan LPJ.
   - Export dokumen ke format PDF (DomPDF).
4. **Feedback Kegiatan & Sentiment Analysis**
   - Formulir feedback interaktif untuk peserta kegiatan.
   - Analisis sentimen otomatis berbasis AI (Positif, Netral, Negatif) terhadap masukan/ulasan kegiatan.
5. **Dashboard Organisasi**
   - Ringkasan statistik kegiatan, total partisipan, status proposal/LPJ, serta distribusi sentimen feedback.

---

## Tech Stack
- **Framework Utama**: Laravel (PHP 8.5)
- **Database**: MySQL / MariaDB (Database Name: `campus_org`)
- **Frontend / Styling**: Blade Components, Livewire, Tailwind CSS
- **Document Export**: DomPDF (`barryvdh/laravel-dompdf`)
- **AI Integration**: Laravel HTTP Client -> LLM API (OpenAI / Gemini / Provider compatible)
- **Interactive Components**: FullCalendar.js (jika diperlukan untuk kalender kegiatan)
- **Testing & Tools**: PHPUnit, Laravel Boost, Pint

---

## Development Phases (Tahapan Pengembangan)
1. **Fase 1: Project Setup & Foundation (Tahap Saat Ini)**
   - Inisialisasi framework Laravel & environment (`.env`).
   - Setup koneksi MySQL (`campus_org`).
   - Setup Laravel Boost & toolings.
   - Dokumentasi proyek (`docs/project-overview.md`).
2. **Fase 2: Authentication & Authorization (Role System)**
   - Implementasi autentikasi & otorisasi role (Admin, Pengurus, Anggota).
   - Seeder & middleware hak akses.
3. **Fase 3: Manajemen Kegiatan (Events)**
   - Migration, Model, Controller, View/Livewire CRUD Kegiatan.
   - Filter & tampilan daftar kegiatan.
4. **Fase 4: Proposal & LPJ dengan AI Helper + Export PDF**
   - Modul pengajuan proposal & LPJ.
   - Integrasi LLM API via Laravel HTTP Client.
   - Export dokumen PDF via DomPDF.
5. **Fase 5: Feedback & Sentiment Analysis + Dashboard**
   - Formulir feedback kegiatan & sentiment scoring AI.
   - Dashboard analitik ringkasan kegiatan & sentimen.
6. **Fase 6: Testing, Refinement & Polishing**
   - Automated testing (PHPUnit feature tests).
   - UI polish & responsive review.

---

## Coding Conventions
- **Bahasa Kode**: Seluruh penamaan variabel, method, class, tabel, dan kolom database wajib menggunakan **Bahasa Inggris** (misal: `Event`, `Proposal`, `user_id`, `is_approved`).
- **Bahasa UI & Dokumentasi**: Antarmuka pengguna (UI), label, pesan validasi, dan dokumen proyek menggunakan **Bahasa Indonesia**.
- **Laravel Best Practices**:
  - Ikuti konvensi Laravel standar (`snake_case` untuk database, `CamelCase` untuk Class, `camelCase` untuk method/variabel).
  - Gunakan Service Class atau Action pattern untuk logika kompleks (misal: integrasi AI / Sentiment Analysis).
  - Gunakan Environment Variable (`.env`) untuk menyimpan kredensial API dan rahasia aplikasi, **TIDAK BOLEH hardcode API key**.
  - Setiap endpoint / action wajib memiliki Validasi Data (Form Request / Livewire validation) dan Otorisasi (Policies / Gates).

---

## Important Technical Decisions
1. **Model Arsitektur Monolitik Modern**: Menggunakan Laravel Blade + Livewire untuk kecepatan iterasi (Vibe Coding) tanpa memisahkan backend dan frontend secara berlebihan.
2. **Keamanan Kredensial**: LLM API key disimpan secara terisolasi di `.env` dan diakses melalui `config/services.php`.
3. **Skalabilitas Basis Data**: Skema basis data dirancang terstruktur dengan kunci asing (foreign key constraint) dan indeks yang optimal untuk query dashboard.
4. **Export Dokumen Standardized**: Menggunakan DomPDF dengan template HTML Blade khusus agar hasil cetak proposal/LPJ konsisten dan rapi.
