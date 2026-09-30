# Dokumentasi Arsitektur Integrasi AI (Proposal & LPJ Module)

Dokumen ini menjelaskan arsitektur, pola perancangan, keamanan, penanganan kesalahan (*error handling*), serta strategi *fallback* dari integrasi Large Language Model (LLM API) pada modul penyusunan dokumen **Proposal & LPJ** dalam **Campus Organization Management System**.

---

## 🎯 Prinsip Utama Integrasi

1. **AI Sebagai Assistant (Bukan Sumber Kebenaran Final)**:
   - Hasil generasi AI dimasukkan ke dalam form editor untuk ditinjau, diperbaiki, dan disesuaikan oleh Pengurus sebelum disimpan atau diajukan (*submit*).
2. **Kepatuhan Keamanan & Privasi PII**:
   - API Key **TIDAK PERNAH** di-hardcode dalam repositori atau disimpan di database.
   - API Key wajib menggunakan *environment variable* (`.env` / `AI_API_KEY`).
   - Data pribadi (*Personally Identifiable Information / PII*) seperti email, password, nomor telepon, dan ID user **TIDAK dikirimkan** ke LLM API. Hanya data publik kegiatan (Judul, Nama Organisasi, Deskripsi, Jadwal, Lokasi, Kuota) yang digunakan dalam prompt.
3. **Resiliensi & Fallback Strategy**:
   - Sistem dilengkapi dengan mekanisme penanganan batas waktu (*timeout*), pengulangan otomatis (*retry strategy*), dan generator *fallback* mandiri jika API luar mengalami kendala atau tidak dikonfigurasi.

---

## 🏗️ Arsitektur & Pemisahan Komponen (Separation of Concerns)

```
[ Frontend Editor (Blade / Alpine.js) ]
                |
                v  AJAX (POST /documents/generate-ai-draft)
[ AiDraftController ]
                |
                v  Dependency Injection
[ AiDocumentServiceInterface / AiDocumentService ]
      /                                     \
     v (Jika Online & Valid)                 v (Jika Offline / Timeout / Rate Limit)
[ LlmAiService ]                      [ FallbackAiService ]
     |
     +--> [ DocumentPromptBuilder ] (Prompt Engineering & PII Filtering)
     |
     +--> [ Laravel HTTP Client ] (Timeout, Retry, Safe Logging without Keys)
     |
     v
[ LLM API (Google Gemini / OpenAI / Custom Provider) ]
```

### Pemisahan Tanggung Jawab Kode:

1. **Configuration (`config/ai.php`)**:
   - Menyimpan konfigurasi penyedia API, model, endpoint, timeout (default 15s), jumlah retry (default 2x), dan toggle `AI_MOCK`.
2. **Contract (`app/Services/Ai/Contracts/AiDocumentServiceInterface.php`)**:
   - Abstraksi interface untuk menjamin pemisahan kontrak dari implementasi konkret.
3. **Prompt Builder (`app/Services/Ai/Prompts/DocumentPromptBuilder.php`)**:
   - Bertanggung jawab memformat prompt naratif dan instruksi struktur JSON baku (tanpa markdown).
   - Memastikan hanya data kegiatan non-sensitif yang diteruskan.
4. **LLM Service (`app/Services/Ai/LlmAiService.php`)**:
   - Mengelola komunikasi HTTP eksternal, pembersihan output JSON (`cleanAndParseJson`), validasi skema data, serta pencatatan log yang aman (*safe logging* tanpa mengekspos API Key).
5. **Fallback Generator (`app/Services/Ai/FallbackAiService.php`)**:
   - Menyediakan draf template bawaan berkualitas tinggi jika API mengalami gawat darurat atau tidak dikonfigurasi.
6. **Main Service (`app/Services/Ai/AiDocumentService.php`)**:
   - Mengatur alur logika utama: memanggil `LlmAiService`, dan jika gagal/mock, mengalihkan ke `FallbackAiService` secara transparan dengan flag `is_fallback`.
7. **Controller (`app/Http/Controllers/AiDraftController.php`)**:
   - Hanya mengurus validasi HTTP request, otorisasi role pengurus/admin, dan pengembalian respon JSON.

---

## 🔑 Konfigurasi Environment (`.env`)

```ini
AI_PROVIDER=gemini
AI_API_KEY=your_gemini_api_key_here
AI_MODEL=gemini-1.5-flash
AI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
AI_TIMEOUT=15
AI_RETRY_TIMES=2
AI_RETRY_SLEEP_MS=500
AI_MOCK=false
```

---

## 🛡️ Keamanan & Logging Aman (Safe Logging)

Seluruh log aktivitas AI pada `storage/logs/laravel.log` menerapkan *safe logging*:
- **Yang dicatat**: Provider, Model Name, Event ID, Document Type, Status Code HTTP, dan pesan error umum.
- **Yang dilarang**: API Key, Authorization Header, atau token sensitif lainnya.

---

## 🧪 Pengujian Automated Tests

Seluruh skenario integrasi AI diuji di `tests/Feature/AiDraftGenerationTest.php` mencakup:
- ✅ **Successful AI Response**: Pengujian respon JSON AI yang valid (via `Http::fake()`).
- ✅ **Timeout Handling**: Simulasi `ConnectionException` / timeout jaringan dan pengalihan ke fallback.
- ✅ **Invalid Response**: Respon string yang bukan JSON valid memicu fallback secara otomatis.
- ✅ **API Error (500 / 429 Rate Limit)**: Respon status error HTTP dari LLM memicu fallback.
- ✅ **Empty Response**: Respon kandidat kosong memicu fallback.
- ✅ **Fallback Strategy**: Verifikasi bahwa draf proposal & LPJ buatan fallback terstruktur dengan baik.
- ✅ **Authorization**: Memastikan anggota biasa dan pengurus organisasi lain ditolak (`403 Forbidden`).
