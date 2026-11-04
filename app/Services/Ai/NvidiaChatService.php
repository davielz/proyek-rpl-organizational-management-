<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NvidiaChatService
{
    public function __construct(
        protected ?ChatbotDatabaseContextService $dbContextService = null
    ) {
        $this->dbContextService ??= app(ChatbotDatabaseContextService::class);
    }

    /**
     * Send user message to NVIDIA AI API (OpenAI compatible endpoint).
     *
     * @param string $message
     * @param array $history Array of previous messages [['role' => 'user'|'assistant', 'content' => string]]
     * @param string $userName
     * @param string $userRole
     * @param \App\Models\User|null $user
     * @return array{success: bool, reply: string, error: string|null}
     */
    public function chat(
        string $message,
        array $history = [],
        string $userName = 'Pengguna',
        string $userRole = 'Anggota',
        ?\App\Models\User $user = null
    ): array {
        $user ??= auth()->user();

        $apiKey = config('ai.api_key') ?: env('NVIDIA_API_KEY');
        $baseUrl = rtrim(config('ai.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $model = config('ai.model', 'meta/llama-3.2-11b-vision-instruct');
        $timeout = (int) config('ai.timeout', 60);
        $maxTokens = (int) config('ai.max_tokens', 450);
        $retryTimes = (int) config('ai.retry_times', 2);
        $retrySleep = (int) config('ai.retry_sleep_ms', 1000);

        if (empty($apiKey)) {
            Log::warning('[NVIDIA AI Chat] API Key tidak dikonfigurasi dalam .env, mengaktifkan local fallback');
            $dbContext = $this->dbContextService ? $this->dbContextService->buildContext($message, $user) : '';
            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];
        }

        $dbContext = $this->dbContextService ? $this->dbContextService->buildContext($message, $user) : '';

        $rolePolicy = match (strtolower($userRole)) {
            'admin' => "PENGGUNA MEMILIKI HAK AKSES ADMINISTRATOR (ADMIN):
- Pengguna berhak mengakses seluruh data sistem (statistik administratif lengkap, seluruh kegiatan termasuk draft, seluruh proposal/LPJ termasuk yang ditolak/pending, catatan review internal, dan seluruh feedback).
- Berikan informasi yang diminta secara komprehensif, faktual, dan transparan sesuai data database yang tersedia.",

            'pengurus' => "PENGGUNA MEMILIKI HAK AKSES PENGURUS ORGANISASI:
- Pengguna berhak mengakses informasi kegiatan terbuka, kegiatan organisasinya, dokumen proposal/LPJ organisasinya, dan feedback kegiatan organisasinya.
- DILARANG memberikan data proposal privat, rincian anggaran, atau catatan review internal milik organisasi lain yang tidak ia kelola.
- DILARANG membocorkan rincian administratif sistem secara global (seperti jumlah/daftar akun admin sistem).",

            default => "PENGGUNA MEMILIKI HAK AKSES ANGGOTA BIASA (ANGGOTA):
- Pengguna HANYA berhak mengakses informasi publik: kegiatan yang aktif dibuka, polling terbuka, ruang diskusi umum, daftar organisasi, dan data partisipasi pribadinya.
- INFORMASI SENSITIF & DILARANG DIBOCORKAN: Dokumen proposal & LPJ internal organisasi (termasuk yang berstatus draft/pending/rejected), rincian anggaran kegiatan, catatan review internal admin, evaluasi sentimen internal, serta data statistik akun admin/pengurus sistem.
- Jika pengguna menanyakan data internal/sensitif tersebut, TOLAK SECARA SOPAN DAN TEGAS dengan menjelaskan bahwa data tersebut bersifat internal/rahasia organisasi dan hanya dapat diakses oleh Admin atau Pengurus terkait."
        };

        $systemPrompt = "Anda adalah 'Asisten AI Kampus' cerdas dan eksklusif untuk Sistem Pengelolaan Organisasi Mahasiswa Kampus.
Pengguna yang sedang berkonsultasi bernama {$userName} dengan peranan: {$userRole}.

AKSES DATABASE SISTEM (REAL-TIME DATA):
Anda memiliki kemampuan dan akses membaca data sistem aplikasi ini yang telah difilter sesuai dengan hak akses peranan pengguna:
\"\"\"
{$dbContext}
\"\"\"

KEBIJAKAN HAK AKSES & KEAMANAN INFORMASI (ROLE-BASED CONFIDENTIALITY):
{$rolePolicy}

ATURAN UTAMA & BATASAN DOMAIN (MANDATORI):
1. Anda HANYA boleh menjawab pertanyaan dan membantu tugas yang berkaitan LANGSUNG dengan sistem aplikasi pengelolaan organisasi kampus ini (fitur: Kegiatan, Proposal & LPJ, Polling & Voting, Ruang Diskusi, Feedback & Sentimen, Pengaturan Profil/Role, serta data yang diizinkan untuk rolenya).
2. Jika pengguna meminta atau menanyakan hal DI LUAR KONTEKS sistem aplikasi ini (misalnya: penulisan kode/coding pemrograman umum seperti Python/Java/C++, tugas kuliah umum, pengetahuan umum, resep, hiburan, atau topik di luar sistem organisasi ini), Anda HARUS MENOLAK PERMINTAAN TERSEBUT SECARA SOPAN DAN TEGAS.
3. Contoh kalimat penolakan: 'Maaf, saya adalah Asisten AI yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat membantu permintaan di luar konteks sistem aplikasi ini (seperti penulisan kode pemrograman atau topik umum).'
4. Jawablah setiap pertanyaan seputar kegiatan, polling, diskusi, dokumen, ulasan, atau statistik dengan merujuk langsung pada data database yang disediakan di atas sesuai batas aksesnya. Jika data yang ditanyakan pengguna tidak tercatat di database atau berada di luar batas izin rolenya, jelaskan dengan sopan.
5. Berikan jawaban yang informatif, solutif, ringkas, dan profesional dalam Bahasa Indonesia, dengan format Markdown yang rapi (gunakan bold, bullet points, atau tabel jika sesuai).
6. Jika ditanya tentang penyusunan proposal, terangkan fitur '✨ Generate Draf dengan AI' yang tersedia di sistem ini.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Append recent conversation history (max 6 messages to keep context concise)
        $trimmedHistory = array_slice($history, -6);
        foreach ($trimmedHistory as $item) {
            if (isset($item['role'], $item['content']) && in_array($item['role'], ['user', 'assistant'])) {
                $messages[] = [
                    'role' => $item['role'],
                    'content' => (string) $item['content'],
                ];
            }
        }

        // Append current user message
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        Log::info('[NVIDIA AI Chat] Sending chat request', [
            'model' => $model,
            'user' => $userName,
            'role' => $userRole,
            'timeout' => $timeout,
            'max_tokens' => $maxTokens,
        ]);

        try {
            $response = Http::timeout($timeout)
                ->retry($retryTimes, $retrySleep, function (Throwable $exception) {
                    Log::warning('[NVIDIA AI Chat] Retrying request due to exception: ' . $exception->getMessage());
                    return true;
                }, throw: false)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => $maxTokens,
                ]);

            if ($response && $response->successful()) {
                $json = $response->json();
                $reply = $json['choices'][0]['message']['content'] ?? null;

                if (!empty($reply)) {
                    return [
                        'success' => true,
                        'reply' => trim($reply),
                        'error' => null,
                    ];
                }
            }

            Log::warning('[NVIDIA AI Chat] HTTP response unsuccessful or empty, activating fallback', [
                'status' => $response ? $response->status() : 'no_response',
                'body' => $response ? ($response->json() ?? $response->body()) : null,
            ]);

            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];

        } catch (ConnectionException $e) {
            Log::warning('[NVIDIA AI Chat] Connection timeout, activating fallback', ['message' => $e->getMessage()]);

            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::error('[NVIDIA AI Chat] Exception error, activating fallback', ['message' => $e->getMessage()]);

            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];
        }
    }

    /**
     * Check if a message is asking for something outside the application scope (e.g. coding, math, general trivia).
     */
    public function isOutOfContext(string $message): bool
    {
        $lower = mb_strtolower($message);

        $outOfContextPatterns = [
            'coding', 'koding', 'python', 'javascript', 'c\+\+', 'java\b', 'golang',
            'html\b', 'css\b', 'bikin script', 'buatkan kode', 'tuliskan kode', 'tulis kode',
            'resep makanan', 'resep masak', 'lirik lagu', 'chord gitar',
            'presiden amerika', 'ibukota negara', 'ramalan cuaca'
        ];

        foreach ($outOfContextPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate an intelligent local fallback response from the loaded database context
     * when the external NVIDIA AI service is temporarily slow or unreachable.
     */
    public function generateLocalFallbackReply(
        string $message,
        string $dbContext,
        string $userName,
        string $userRole,
        ?\App\Models\User $user = null
    ): string {
        // 1. Check if user asked something completely out of context
        if ($this->isOutOfContext($message)) {
            return "Maaf, saya adalah Asisten AI yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat membantu permintaan di luar konteks sistem aplikasi ini (seperti penulisan kode pemrograman atau topik umum).";
        }

        $lower = mb_strtolower($message);
        $isAnggota = strtolower($userRole) === 'anggota';
        $isAdmin = strtolower($userRole) === 'admin';

        $notice = "\n\n*(Catatan: Informasi di atas disajikan langsung dari ringkasan database sistem kampus kami secara real-time karena server AI eksternal sedang mengalami antrean/latensi tinggi).*";

        // Query: Events / Kegiatan
        if (preg_match('/(kegiatan|acara|event|workshop|seminar|lomba|buka|daftar)/i', $lower)) {
            $events = \App\Models\Event::with('organization')
                ->where('status', 'active')
                ->latest('created_at')
                ->take(5)
                ->get();

            if ($events->isEmpty()) {
                return "Halo {$userName}, saat ini belum ada kegiatan mahasiswa yang sedang aktif dibuka pendaftarannya di database sistem." . $notice;
            }

            $list = [];
            foreach ($events as $e) {
                $org = $e->organization->name ?? 'Umum';
                $quota = $e->quota ? " (Kuota: {$e->quota} peserta)" : '';
                $start = $e->start_time ? $e->start_time->translatedFormat('d M Y, H:i') . ' WIB' : 'Waktu diumumkan segera';
                $list[] = "• **{$e->title}**\n  - Penyelenggara: {$org}\n  - Waktu: {$start}\n  - Lokasi: {$e->location}{$quota}";
            }

            return "Halo {$userName}! Berikut adalah daftar kegiatan mahasiswa yang sedang aktif dibuka di database sistem:\n\n" . implode("\n\n", $list) . $notice;
        }

        // Query: Polling / Voting
        if (preg_match('/(polling|voting|vote|suara|jajak)/i', $lower)) {
            $polls = \App\Models\Poll::with('options')
                ->where('status', 'active')
                ->latest('created_at')
                ->take(4)
                ->get();

            if ($polls->isEmpty()) {
                return "Halo {$userName}, saat ini tidak ada polling atau voting yang sedang aktif dibuka di sistem." . $notice;
            }

            $list = [];
            foreach ($polls as $p) {
                $opts = $p->options->pluck('option_text')->implode(', ');
                $deadline = $p->deadline ? $p->deadline->translatedFormat('d M Y, H:i') . ' WIB' : 'Tanpa batas waktu';
                $list[] = "• **{$p->title}** (Total suara: {$p->totalVotes()})\n  - Batas waktu: {$deadline}\n  - Opsi: {$opts}";
            }

            return "Halo {$userName}! Berikut polling/voting yang saat ini sedang aktif:\n\n" . implode("\n\n", $list) . $notice;
        }

        // Query: Ruang Diskusi / Topik
        if (preg_match('/(diskusi|forum|topik|komentar)/i', $lower)) {
            $discussions = \App\Models\DiscussionPost::with('user')->latest('created_at')->take(4)->get();

            if ($discussions->isEmpty()) {
                return "Halo {$userName}, belum ada topik diskusi baru di Ruang Diskusi saat ini." . $notice;
            }

            $list = [];
            foreach ($discussions as $d) {
                $author = $d->user->name ?? 'Mahasiswa';
                $list[] = "• **{$d->title}** (oleh {$author}, {$d->comments()->count()} komentar)";
            }

            return "Halo {$userName}! Berikut adalah topik diskusi terkini di Ruang Diskusi kampus:\n\n" . implode("\n", $list) . "\n\nAnda dapat mengunjungi menu **Ruang Diskusi** untuk ikut berpartisipasi." . $notice;
        }

        // Query: Dokumen / Proposal / LPJ
        if (preg_match('/(dokumen|proposal|lpj|anggaran|pengajuan)/i', $lower)) {
            if ($isAnggota) {
                return "Halo {$userName}. Sesuai kebijakan hak akses sistem, dokumen proposal & LPJ internal organisasi bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi serta Administrator. Anda dapat memantau status proposal yang Anda ajukan sendiri melalui menu profil akun Anda." . $notice;
            }

            $docs = \App\Models\Document::latest('created_at')->take(5)->get();
            if ($docs->isEmpty()) {
                return "Halo {$userName}, belum ada dokumen proposal atau LPJ yang tercatat di database." . $notice;
            }

            $list = [];
            foreach ($docs as $d) {
                $list[] = "• **{$d->title}** (" . strtoupper($d->type) . " - Status: " . ucfirst($d->status) . ")";
            }

            return "Halo {$userName}! Berikut ringkasan dokumen proposal & LPJ terkini:\n\n" . implode("\n", $list) . $notice;
        }

        // Query: Organisasi / UKM
        if (preg_match('/(organisasi|ukm|himpunan|daftar ukm)/i', $lower)) {
            $orgs = \App\Models\Organization::take(6)->get();
            if ($orgs->isEmpty()) {
                return "Halo {$userName}, belum ada data organisasi mahasiswa yang terdaftar di database." . $notice;
            }

            $list = [];
            foreach ($orgs as $o) {
                $list[] = "• **{$o->name}**" . ($o->code ? " [{$o->code}]" : '') . ": " . \Illuminate\Support\Str::limit($o->description, 70);
            }

            return "Halo {$userName}! Berikut adalah beberapa organisasi/UKM yang terdaftar di kampus:\n\n" . implode("\n", $list) . $notice;
        }

        // Query: Statistik / Ringkasan Sistem
        if (preg_match('/(statistik|ringkasan|overview|total|berapa banyak)/i', $lower)) {
            $totalEvents = \App\Models\Event::where('status', 'active')->count();
            $totalPolls = \App\Models\Poll::where('status', 'active')->count();
            $totalOrgs = \App\Models\Organization::count();
            $totalDiscussions = \App\Models\DiscussionPost::count();

            $reply = "Halo {$userName}! Berikut ringkasan data statistik sistem saat ini:\n\n" .
                "• **Organisasi/UKM Terdaftar**: {$totalOrgs} organisasi\n" .
                "• **Kegiatan Mahasiswa Aktif**: {$totalEvents} kegiatan dibuka\n" .
                "• **Polling/Voting Berjalan**: {$totalPolls} polling aktif\n" .
                "• **Topik Ruang Diskusi**: {$totalDiscussions} topik diskusi";

            if ($isAdmin) {
                $totalUsers = \App\Models\User::count();
                $totalDocs = \App\Models\Document::count();
                $reply .= "\n• **Total Pengguna Terdaftar**: {$totalUsers} pengguna\n" .
                    "• **Total Dokumen Proposal/LPJ**: {$totalDocs} dokumen";
            }

            return $reply . $notice;
        }

        // Default General Helpful Answer
        return "Halo {$userName}! Saya adalah Asisten AI Kampus. Saat ini layanan AI eksternal sedang mengalami antrean jaringan yang padat, namun saya dapat membantu Anda membaca data sistem aplikasi secara langsung:\n\n" .
            "1. **Kegiatan Mahasiswa**: Tanyakan kegiatan yang sedang aktif dibuka pendaftarannya.\n" .
            "2. **Polling & Voting**: Tanyakan polling yang sedang berjalan untuk memberikan suara.\n" .
            "3. **Ruang Diskusi**: Cari tahu topik diskusi terbaru seputar kampus.\n" .
            "4. **Organisasi/UKM**: Dapatkan daftar organisasi mahasiswa terdaftar.\n" .
            "5. **Statistik Sistem**: Tanyakan ringkasan data terkini aplikasi." . $notice;
    }
}
