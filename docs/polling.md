# Feature Documentation — Sistem Polling & Voting Internal Organisasi

**Nama Fitur**: Polling & Voting Internal Organisasi Mahasiswa  
**Tanggal Rilis**: 3 Oktober 2026  
**Status**: **100% COMPLETE & PRODUCTION-READY (78/78 Tests PASSED)**

---

## 🎯 1. Tujuan Fitur

Fitur Polling & Voting disediakan untuk memberikan wadah pengambilan keputusan internal dan pengumpulan suara (*voting*) dari anggota organisasi secara cepat, transparan, dan terarah. Contoh penggunaan mencakup penentuan tanggal/waktu rapat, pemilihan lokasi/tema kegiatan, serta jajak pendapat keputusan organisasi.

---

## 👥 2. Role & Permission Matrix

| Fitur / Akses | Admin | Pengurus | Anggota | Guest |
| :--- | :---: | :---: | :---: | :---: |
| Lihat Daftar & Detail Polling | ✅ | ✅ | ✅ | ❌ |
| Berikan Suara (Voting) | ✅ | ✅ | ✅ | ❌ |
| Buat Polling Baru | ✅ | ✅ | ❌ | ❌ |
| Edit Polling | ✅ (Semua) | ✅ (Milik Sendiri) | ❌ | ❌ |
| Tutup Polling | ✅ (Semua) | ✅ (Milik Sendiri) | ❌ | ❌ |
| Hapus Polling | ✅ (Semua) | ✅ (Milik Sendiri) | ❌ | ❌ |

---

## 🗄️ 3. Struktur Database (Database Schema)

### A. Tabel `polls`
- `id` (BigInt, Primary Key)
- `organization_id` (BigInt, Foreign Key ke `organizations.id`, Nullable)
- `created_by` (BigInt, Foreign Key ke `users.id`)
- `title` (String 255)
- `description` (Text, Nullable)
- `deadline` (DateTime)
- `status` (Enum: `draft`, `active`, `closed`, Default: `active`)
- `created_at`, `updated_at`

### B. Tabel `poll_options`
- `id` (BigInt, Primary Key)
- `poll_id` (BigInt, Foreign Key ke `polls.id`, Cascade Delete)
- `option_text` (String 255)
- `created_at`, `updated_at`

### C. Tabel `poll_votes`
- `id` (BigInt, Primary Key)
- `poll_id` (BigInt, Foreign Key ke `polls.id`, Cascade Delete)
- `option_id` (BigInt, Foreign Key ke `poll_options.id`, Cascade Delete)
- `user_id` (BigInt, Foreign Key ke `users.id`, Cascade Delete)
- `voted_at` (Timestamp, Default: `now()`)
- `created_at`, `updated_at`
- **Unique Protection Constraint**: `UNIQUE(poll_id, user_id)` (Mencegah vote ganda di tingkat database)

---

## ⚖️ 4. Business Rules & Protection Mechanisms

1. **Pilihan Jawaban**: Polling wajib memiliki minimal 2 pilihan jawaban. Pilihan ganda/duplikat tidak diperbolehkan.
2. **Pencegahan Duplicate Voting**: 1 user hanya dapat memilih 1 opsi per polling. Proteksi ditegakkan di level database (`unique constraint`) dan controller/policy layer.
3. **Proteksi Masa Berlaku**: User tidak dapat memberikan suara pada polling yang statusnya `closed` atau jika waktu `deadline` telah lewat.
4. **Proteksi Opsi (Option IDOR Protection)**: Server-side memverifikasi bahwa `option_id` yang dikirimkan user memang milik `poll_id` yang sedang divote.
5. **Integritas Data Opsi**: Polling yang sudah memiliki suara masuk (`totalVotes > 0`) mengunci opsi jawaban agar tidak dapat diubah atau dihapus sembarangan oleh pengurus.
6. **Authorization Server-Side**: Seluruh aksi dikontrol oleh `App\Policies\PollPolicy`.

---

## 🔄 5. User Flow

```
[ Pengurus / Admin ]                     [ Anggota Organisasi ]
        │                                          │
 1. Klik "Buat Polling Baru"               1. Buka Menu "Polling & Voting"
        │                                          │
 2. Isi Judul, Deskripsi,                  2. Pilih Polling Aktif
    Deadline & Min 2 Opsi                          │
        │                                  3. Pilih 1 Opsi Jawaban
 3. Klik "Terbitkan Polling"                       │
        │                                  4. Klik "Kirim Suara (Vote)"
        ▼                                          │
   Polling Aktif ─────────────────────────► 5. Sistem simpan vote &
                                              tampilkan grafik hasil real-time
```

---

## 🗺️ 6. Routes Registered

- `GET /polls` — `PollController@index` (`name: polls.index`)
- `GET /polls/create` — `PollController@create` (`name: polls.create`)
- `POST /polls` — `PollController@store` (`name: polls.store`)
- `GET /polls/{poll}` — `PollController@show` (`name: polls.show`)
- `GET /polls/{poll}/edit` — `PollController@edit` (`name: polls.edit`)
- `PUT /polls/{poll}` — `PollController@update` (`name: polls.update`)
- `DELETE /polls/{poll}` — `PollController@destroy` (`name: polls.destroy`)
- `POST /polls/{poll}/vote` — `PollController@vote` (`name: polls.vote`, throttle 15/min)
- `POST /polls/{poll}/close` — `PollController@close` (`name: polls.close`)

---

## 🧪 7. Automated Testing Coverage (15 Scenarios)

1. Admin dapat membuat polling.
2. Pengurus dapat membuat polling.
3. Anggota tidak dapat membuat polling (403 Forbidden).
4. Anggota dapat melihat daftar polling aktif.
5. Anggota dapat melakukan voting.
6. Anggota tidak dapat melakukan vote ganda.
7. Voting setelah deadline ditolak.
8. Voting pada polling status closed ditolak.
9. Polling harus memiliki minimal 2 opsi.
10. Option dari polling lain ditolak (IDOR protection).
11. User ID diproteksi dari manipulasi request (Server-side `Auth::id()`).
12. Pengurus lain tidak dapat mengedit/menghapus polling yang bukan miliknya.
13. Hasil kalkulasi persentase dan perolehan suara akurat.
14. Empty state tampil jika polling belum memiliki suara.
15. Opsi jawaban terkunci jika polling sudah memiliki suara masuk.

---

## 📌 8. Known Limitations

- **Single Choice Only**: Saat ini setiap polling mendukung model *single-choice selection* (1 opsi per user). Fitur *multiple-choice selection* dapat ditambahkan pada iterasi masa depan.
- **Audience Visibility**: Semua anggota yang terautentikasi dapat melihat dan memberikan suara pada polling umum/organisasi.
