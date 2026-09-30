<?php

namespace App\Services\Ai;

use App\Models\Event;

class FallbackAiService
{
    /**
     * Generate fallback draft content when AI service is offline, rate limited, or unconfigured.
     */
    public function generateFallback(Event $event, string $type): array
    {
        $orgName = $event->organization?->name ?? 'Organisasi Kampus';
        $eventTitle = $event->title;

        if ($type === 'proposal') {
            return [
                'title' => "Proposal Kegiatan: {$eventTitle}",
                'background' => "Dalam rangka meningkatkan kompetensi dan peran aktif mahasiswa di lingkungan perguruan tinggi, {$orgName} berinisiatif menyelenggarakan kegiatan \"{$eventTitle}\". Kegiatan ini dirancang sebagai sarana pembelajaran interaktif dan penguatan kolaborasi antar mahasiswa dalam menghadapi perkembangan teknologi serta tantangan masa depan.\n\nMelalui program ini, diharapkan para peserta dapat memperoleh wawasan praktis, wawasan kepemimpinan, serta jaringan profesional yang bermanfaat bagi pengembangan akademik dan karir.",
                'objectives' => "1. Meningkatkan pemahaman teknis dan softskill peserta terkait {$eventTitle}.\n2. Mempererat hubungan silaturahmi dan sinergi antar pengurus {$orgName} serta seluruh sivitas akademika.\n3. Memberikan wadah kontribusi nyata mahasiswa dalam kegiatan kemahasiswaan yang produktif.",
                'budget_plan' => "ESTIMASI RENCANA ANGGARAN BIAYA:\n- Konsumsi & Logistik Peserta: Rp 1.500.000\n- Perlengkapan, Banner & Spanduk: Rp 750.000\n- Honorarium Pemateri / Fasilitator: Rp 1.000.000\n- Biaya Kesekretariatan & Sertifikat: Rp 500.000\n--------------------------------------------\nTOTAL ESTIMASI KEBUTUHAN DANA: Rp 3.750.000",
                'summary_content' => "Kegiatan direncanakan berlangsung di {$event->location} secara terstruktur mencakup pembukaan resmi, sesi pematerian utama, workshop interaktif, serta sesi evaluasi dan penutup.",
            ];
        }

        return [
            'title' => "Laporan Pertanggungjawaban (LPJ): {$eventTitle}",
            'background' => "Kegiatan \"{$eventTitle}\" yang diselenggarakan oleh {$orgName} telah selesai dilaksanakan dengan lancar. Dokumen Laporan Pertanggungjawaban (LPJ) ini disusun sebagai wujud transparansi, akuntabilitas, dan bahan evaluasi menyeluruh atas pelaksanaan program kerja tersebut.",
            'objectives' => "1. Melaporkan realisasi pencapaian tujuan dan indikator keberhasilan kegiatan.\n2. Menyajikan rincian pertanggungjawaban penerimaan dan penggunaan anggaran secara transparan.\n3. Menyampaikan catatan evaluasi dan perbaikan untuk penyelenggaraan di masa mendatang.",
            'budget_plan' => "LAPORAN REALISASI ANGGARAN BIAYA:\n- Total Penerimaan Dana: Rp 3.750.000\n- Realisasi Pengeluaran Aktual: Rp 3.550.000\n--------------------------------------------\nSISA SALDO / DANA EFISIENSI: Rp 200.000 (Dikembalikan ke Kas Organisasi)",
            'summary_content' => "Pelaksanaan acara berjalan sesuai jadwal di {$event->location} dengan partisipasi aktif peserta. Evaluasi menunjukkan tingkat kepuasan peserta yang sangat baik.",
        ];
    }
}
