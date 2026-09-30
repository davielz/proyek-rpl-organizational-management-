<?php

namespace App\Services\Ai\Prompts;

use App\Models\Event;

class DocumentPromptBuilder
{
    /**
     * Build system and user prompt for LLM API.
     * Only non-sensitive event data is passed. No personal user PII.
     */
    public function build(Event $event, string $type): string
    {
        $docTypeName = $type === 'proposal' ? 'Proposal Kegiatan' : 'Laporan Pertanggungjawaban (LPJ)';
        $orgName = $event->organization?->name ?? 'Organisasi Kampus';
        $eventTitle = $event->title;
        $description = $event->description ?? 'Kegiatan organisasi mahasiswa kampus.';
        $location = $event->location ?? 'Kampus';
        $startTime = $event->start_time ? $event->start_time->format('d F Y H:i') : 'Sesuai Jadwal';
        $endTime = $event->end_time ? $event->end_time->format('d F Y H:i') : 'Selesai';
        $quota = $event->quota ? "{$event->quota} Peserta" : 'Terbuka Umum';

        if ($type === 'proposal') {
            return <<<PROMPT
Anda adalah asisten kecerdasan buatan (AI) profesional untuk administrasi organisasi kemahasiswaan kampus.
Tugas Anda adalah menyusun draf awal dokumen {$docTypeName} berbasis data kegiatan berikut.

DATA KEGIATAN:
- Nama Kegiatan: {$eventTitle}
- Penyelenggara: {$orgName}
- Deskripsi Singkat: {$description}
- Lokasi: {$location}
- Waktu Pelaksanaan: {$startTime} s.d. {$endTime}
- Target Kuota: {$quota}

PETUNJUK FORMAT:
Respon HARUS berupa JSON murni tanpa markdown formatting (tanpa ```json ... ```) dengan struktur tepat sebagai berikut:
{
  "title": "Proposal Kegiatan: {$eventTitle}",
  "background": "Latar belakang naratif lengkap dan profesional (3-4 paragraf) mengapa kegiatan ini penting bagi mahasiswa dan kampus.",
  "objectives": "Maksud dan tujuan kegiatan dalam bentuk poin-poin bernomor (1, 2, 3...) secara jelas dan terukur.",
  "budget_plan": "Rincian estimasi anggaran biaya (pos pengeluaran, konsumsi, perlengkapan, publikasi, dll) dengan nilai nominal yang rasional.",
  "summary_content": "Ringkasan skenario pelaksanaan acara, rundown singkat, dan output/outcome yang diharapkan dari kegiatan ini."
}

Gunakan Bahasa Indonesia baku, formal, dan sesuai kaidah penulisan administrasi kampus yang baik.
PROMPT;
        }

        return <<<PROMPT
Anda adalah asisten kecerdasan buatan (AI) profesional untuk administrasi organisasi kemahasiswaan kampus.
Tugas Anda adalah menyusun draf awal dokumen {$docTypeName} (LPJ) berbasis data kegiatan yang telah selesai berikut.

DATA KEGIATAN:
- Nama Kegiatan: {$eventTitle}
- Penyelenggara: {$orgName}
- Deskripsi Singkat: {$description}
- Lokasi: {$location}
- Waktu Pelaksanaan: {$startTime} s.d. {$endTime}
- Kuota Peserta: {$quota}

PETUNJUK FORMAT:
Respon HARUS berupa JSON murni tanpa markdown formatting (tanpa ```json ... ```) dengan struktur tepat sebagai berikut:
{
  "title": "Laporan Pertanggungjawaban (LPJ): {$eventTitle}",
  "background": "Laporan pendahuluan naratif profesional mengenai keterlaksanaan kegiatan {$eventTitle} oleh {$orgName}.",
  "objectives": "Poin-poin ketercapaian maksud dan tujuan kegiatan (1, 2, 3...) yang telah berhasil direalisasikan.",
  "budget_plan": "Laporan pertanggungjawaban realisasi anggaran biaya (penerimaan dana, pengeluaran riil, dan saldo akhir).",
  "summary_content": "Ringkasan jalannya acara, statistik keikutsertaan peserta, evaluasi kendala, serta rekomendasi untuk kegiatan berikutnya."
}

Gunakan Bahasa Indonesia baku, formal, dan sesuai kaidah penulisan administrasi kampus yang baik.
PROMPT;
    }
}
