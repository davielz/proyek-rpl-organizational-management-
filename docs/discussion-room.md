# Dokumentasi Fitur — Ruang Diskusi (Discussion Room)

## 1. Tujuan Fitur
Ruang Diskusi (Discussion Room) adalah forum komunikasi internal sederhana yang memungkinkan setiap anggota organisasi terdaftar (authenticated user) untuk berbagi pandangan, menyampaikan ide, serta berdiskusi secara terorganisir melalui postingan dan komentar.

---

## 2. User Flow
1. **Navigasi ke Ruang Diskusi**: User yang telah login memilih menu "Ruang Diskusi" dari sidebar.
2. **Melihat Katalog Postingan**: User disajikan daftar postingan terbaru yang disusun secara kronologis (terbaru di atas) dengan pagination.
3. **Membuat Postingan**:
   - User mengklik tombol "Buat Diskusi".
   - User mengisi Judul Diskusi dan Isi Diskusi.
   - Setelah disimpan, postingan akan muncul pada katalog dan user dialihkan kembali ke daftar diskusi atau detail postingan.
4. **Melihat Detail Postingan & Komentar**:
   - User mengklik judul postingan untuk membaca isi lengkap dan daftar komentar.
5. **Memberikan Komentar**:
   - Pada halaman detail, user mengisi form "Tulis Komentar" dan menekan tombol "Kirim Komentar".
6. **Edit / Hapus Postingan & Komentar**:
   - Pemilik (Author) postingan/komentar dapat mengedit atau menghapus konten miliknya.
   - Admin organisasi memiliki hak moderasi untuk menghapus postingan atau komentar yang melanggar ketentuan.

---

## 3. Database Structure

### Tabel: `discussion_posts`
| Field | Type | Description |
|---|---|---|
| `id` | BigIncrements | Primary Key |
| `user_id` | Foreign ID (`users.id`) | Foreign key pembuat postingan (Cascade Delete) |
| `organization_id` | Foreign ID (`organizations.id`) | Foreign key organisasi (Nullable, Cascade Delete) |
| `title` | String | Judul postingan diskusi |
| `content` | Text | Isi/konten lengkap postingan |
| `created_at` | Timestamp | Tanggal & waktu pembuatan |
| `updated_at` | Timestamp | Tanggal & waktu pembaruan |

### Tabel: `discussion_comments`
| Field | Type | Description |
|---|---|---|
| `id` | BigIncrements | Primary Key |
| `discussion_post_id` | Foreign ID (`discussion_posts.id`) | Foreign key postingan terkait (Cascade Delete) |
| `user_id` | Foreign ID (`users.id`) | Foreign key pembuat komentar (Cascade Delete) |
| `content` | Text | Isi/konten komentar |
| `created_at` | Timestamp | Tanggal & waktu pembuatan |
| `updated_at` | Timestamp | Tanggal & waktu pembaruan |

---

## 4. Authorization & Moderation Rules

Sistem otorisasi menggunakan Laravel Policy (`DiscussionPostPolicy` dan `DiscussionCommentPolicy`).

### Business & Authorization Matrix:
| Aksi | Guest | Authenticated User (Anggota) | Author (Pemilik Konten) | Admin |
|---|---|---|---|---|
| Melihat Daftar Post | ❌ Redirect Login | ✅ Ya | ✅ Ya | ✅ Ya |
| Melihat Detail Post | ❌ Redirect Login | ✅ Ya | ✅ Ya | ✅ Ya |
| Membuat Post | ❌ Redirect Login | ✅ Ya | ✅ Ya | ✅ Ya |
| Mengedit Post | ❌ | ❌ | ✅ Ya | ❌ (Hanya Author) |
| Menghapus Post | ❌ | ❌ | ✅ Ya | ✅ Moderasi |
| Membuat Komentar | ❌ Redirect Login | ✅ Ya | ✅ Ya | ✅ Ya |
| Mengedit Komentar | ❌ | ❌ | ✅ Ya | ❌ (Hanya Author) |
| Menghapus Komentar | ❌ | ❌ | ✅ Ya | ✅ Moderasi |

---

## 5. Business Rules & Security
1. **Server-Side Authorization**: Seluruh otorisasi dipaksakan di controller via `$this->authorize(...)`.
2. **IDOR & Mass Assignment Protection**: `user_id` selalu diset secara otomatis dari `$request->user()->id` di server-side.
3. **Pembersihan Comment Orphan**: Cascade delete database menjamin ketika postingan dihapus, seluruh komentar terkait terhapus secara otomatis.
4. **Validasi Input**:
   - Post Title & Content wajib diisi (`required`).
   - Comment Content wajib diisi (`required`). Tidak menerima komentar kosong.
5. **XSS Protection**: Output diproses dengan escaping HTML standar Blade (`{{ $content }}`).

---

## 6. Route Registry

Semua route berada di bawah middleware `auth`:

```php
Route::middleware('auth')->group(function () {
    Route::get('/discussions', [DiscussionController::class, 'index'])->name('discussions.index');
    Route::get('/discussions/create', [DiscussionController::class, 'create'])->name('discussions.create');
    Route::post('/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
    Route::get('/discussions/{discussionPost}', [DiscussionController::class, 'show'])->name('discussions.show');
    Route::get('/discussions/{discussionPost}/edit', [DiscussionController::class, 'edit'])->name('discussions.edit');
    Route::put('/discussions/{discussionPost}', [DiscussionController::class, 'update'])->name('discussions.update');
    Route::delete('/discussions/{discussionPost}', [DiscussionController::class, 'destroy'])->name('discussions.destroy');

    Route::post('/discussions/{discussionPost}/comments', [DiscussionController::class, 'storeComment'])->name('discussions.comments.store');
    Route::put('/discussion-comments/{discussionComment}', [DiscussionController::class, 'updateComment'])->name('discussion-comments.update');
    Route::delete('/discussion-comments/{discussionComment}', [DiscussionController::class, 'destroyComment'])->name('discussion-comments.destroy');
});
```

---

## 7. Testing
Pengujian menyeluruh terdapat pada file `tests/Feature/DiscussionRoomTest.php`, mencakup 28 skenario wajib:
- Pengujian otorisasi Guest vs Authenticated User
- Validasi input (judul, isi, komentar)
- Otorisasi edit dan hapus (Author vs Other Users vs Admin)
- Uji integritas relasi dan pencegahan orphan comments
- Security tests (IDOR, direct request manipulation)
- UI states (Empty states, validation feedback, success messages)

---

## 8. Known Limitations
- Tidak ada fitur chat real-time (WebSockets) sesuai batasan spesifikasi.
- Tidak ada fitur direct message atau privat chat antar user.
- Tidak ada fitur sistem reaksi/like/upvote pada postingan atau komentar.
