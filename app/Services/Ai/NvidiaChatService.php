<?php

namespace App\Services\Ai;

use App\Models\DiscussionPost;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NvidiaChatService
{
    public function __construct(
        protected ?ChatbotDatabaseContextService $dbContextService = null
    ) {
        $this->dbContextService ??= app(ChatbotDatabaseContextService::class);
    }

    /**
     * Send user message to NVIDIA AI API (OpenAI compatible endpoint) for AI Assistant.
     *
     * @param  array  $history  Array of previous messages [['role' => 'user'|'assistant', 'content' => string]]
     * @return array{success: bool, reply: string, error: string|null}
     */
    public function chat(
        string $message,
        array $history = [],
        string $userName = 'Pengguna',
        string $userRole = 'Anggota',
        ?User $user = null
    ): array {
        $user ??= auth()->user();

        // 1. Strict Out-of-Context pre-check (e.g. general coding requests, python, homework)
        if ($this->isOutOfContext($message)) {
            return [
                'success' => true,
                'reply' => 'Maaf, saya adalah AI Assistant yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat melayani permintaan di luar konteks sistem ini (seperti penulisan kode pemrograman atau topik umum di luar aplikasi).',
                'error' => null,
            ];
        }

        // 2. Sensitive data request check for unprivileged users (Anggota)
        if ($this->isSensitiveDataRequestForAnggota($message, $userRole)) {
            return [
                'success' => true,
                'reply' => "Halo {$userName}. Sesuai kebijakan hak akses sistem, dokumen proposal & LPJ internal organisasi bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi serta Administrator. Anda dapat memantau status proposal yang Anda ajukan sendiri melalui menu profil akun Anda.",
                'error' => null,
            ];
        }

        $apiKey = config('ai.api_key');
        if ($apiKey === null) {
            $apiKey = env('NVIDIA_API_KEY');
        }
        $baseUrl = rtrim(config('ai.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $rawModel = config('ai.model', 'nvidia/nemotron-3.5-bolt-e4b');
        $model = preg_replace('/^nvidia\/nvidia\//', 'nvidia/', trim($rawModel));
        $timeout = (int) config('ai.timeout', 60);
        $maxTokens = (int) config('ai.max_tokens', 1000);
        $retryTimes = (int) config('ai.retry_times', 2);
        $retrySleep = (int) config('ai.retry_sleep_ms', 1000);

        $dbContext = $this->dbContextService ? $this->dbContextService->buildContext($message, $user) : '';

        if (empty($apiKey) || config('ai.mock', false)) {
            Log::warning('[AI Assistant] API Key tidak dikonfigurasi dalam .env atau mode mock aktif, mengaktifkan local fallback');

            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];
        }

        $rolePolicy = match (strtolower($userRole)) {
            'admin' => 'PENGGUNA MEMILIKI HAK AKSES ADMINISTRATOR (ADMIN):
- Pengguna berhak mengakses seluruh data sistem (statistik administratif lengkap, seluruh kegiatan termasuk draft, seluruh proposal/LPJ termasuk yang ditolak/pending, catatan review internal, dan seluruh feedback).
- Berikan informasi yang diminta secara komprehensif, faktual, dan transparan sesuai data database yang tersedia.',

            'pengurus' => 'PENGGUNA MEMILIKI HAK AKSES PENGURUS ORGANISASI:
- Pengguna berhak mengakses informasi kegiatan terbuka, kegiatan organisasinya, dokumen proposal/LPJ organisasinya, dan feedback kegiatan organisasinya.
- DILARANG memberikan data proposal privat, rincian anggaran, atau catatan review internal milik organisasi lain yang tidak ia kelola.
- DILARANG membocorkan rincian administratif sistem secara global (seperti jumlah/daftar akun admin sistem).',

            default => 'PENGGUNA MEMILIKI HAK AKSES ANGGOTA BIASA (ANGGOTA):
- Pengguna HANYA berhak mengakses informasi publik: kegiatan yang aktif dibuka, polling terbuka, ruang diskusi umum, daftar organisasi, dan data partisipasi pribadinya.
- INFORMASI SENSITIF & DILARANG DIBOCORKAN: Dokumen proposal & LPJ internal organisasi (termasuk yang berstatus draft/pending/rejected), rincian anggaran kegiatan, catatan review internal admin, evaluasi sentimen internal, serta data statistik akun admin/pengurus sistem.
- Jika pengguna menanyakan data internal/sensitif tersebut, TOLAK SECARA SOPAN DAN TEGAS dengan menjelaskan bahwa data tersebut bersifat internal/rahasia organisasi dan hanya dapat diakses oleh Admin atau Pengurus terkait.'
        };

        $systemPrompt = "Nama Anda adalah 'AI Assistant'. Anda adalah asisten cerdas resmi untuk Sistem Pengelolaan Organisasi Mahasiswa Kampus.
Pengguna yang sedang berkonsultasi bernama {$userName} dengan peranan: {$userRole}.

AKSES DATABASE SISTEM (REAL-TIME DATA):
Anda memiliki kemampuan dan akses membaca data sistem aplikasi ini yang telah difilter secara aman sesuai hak akses peran pengguna:
\"\"\"
{$dbContext}
\"\"\"

KEBIJAKAN HAK AKSES & KEAMANAN INFORMASI (ROLE-BASED CONFIDENTIALITY):
{$rolePolicy}

ATURAN UTAMA & BATASAN DOMAIN (MANDATORI):
1. Anda HANYA boleh menjawab pertanyaan dan membantu tugas yang berkaitan LANGSUNG dengan sistem aplikasi pengelolaan organisasi kampus ini (fitur: Kegiatan & Pendaftaran, Proposal & LPJ Organisasi, Polling & Voting, Ruang Diskusi, Feedback Kegiatan, serta data profil pengguna).
2. Jika pengguna meminta atau menanyakan hal DI LUAR KONTEKS sistem aplikasi ini (misalnya: penulisan kode/coding pemrograman seperti Python, JavaScript, Java, C++, pembuatan website di luar konteks, tugas mata kuliah umum, resep makanan, ramalan, hiburan, atau topik umum lainnya), Anda HARUS MENOLAK PERMINTAAN TERSEBUT SECARA SOPAN DAN TEGAS.
3. Kalimat penolakan resmi out-of-context: 'Maaf, saya adalah AI Assistant yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat melayani permintaan di luar konteks sistem ini (seperti penulisan kode pemrograman atau topik umum di luar aplikasi).'
4. JANGAN PERNAH membeberkan informasi sensitif (dokumen proposal/LPJ organisasi, rincian anggaran, catatan review admin, kritik evaluasi sentimen, password, atau identitas admin rahasia) kepada pengguna biasa (Anggota). Informasi sensitif hanya diizinkan untuk Admin dan Pengurus organisasi terkait.
5. Jawablah setiap pertanyaan seputar kegiatan, polling, diskusi, dokumen, ulasan, atau statistik dengan merujuk langsung pada data database yang disediakan di atas sesuai batas aksesnya. Jika data yang ditanyakan pengguna tidak tercatat di database atau berada di luar batas izin rolenya, jelaskan dengan sopan.
6. Berikan jawaban yang informatif, solutif, ringkas, dan profesional dalam Bahasa Indonesia, dengan format Markdown yang rapi (gunakan bold, bullet points, atau tabel jika sesuai).
7. KETENTUAN PRESISI TERMINOLOGI 'PENGGUNA' VS 'ANGGOTA' (SANGAT KRUSIAL):
   - 'Pengguna' (User / Akun) = Seluruh akun yang ada di sistem (gabungan Admin + Pengurus + Anggota).
   - 'Anggota' (Member) = Khusus pengguna yang memiliki peranan (role) 'anggota' (BUKAN Admin dan BUKAN Pengurus).
   - JIKA pengguna bertanya 'Berapa total anggota?', 'Jumlah anggota?', atau pertanyaan serupa seputar anggota, Anda HARUS MENJAWAB dengan jumlah pengguna dengan peranan 'anggota' (misalnya jika data menunjukkan Anggota: 14, Pengurus: 1, Admin: 1, maka jawab: 'Total anggota yang terdaftar adalah 14 orang'). DILARANG KERAS menjawab total seluruh pengguna (16 orang) sebagai jawaban dari total anggota!
   - JIKA pengguna bertanya 'Berapa total pengguna?' atau 'Berapa total user sistem?', barulah Anda menjawab total seluruh pengguna (16 orang) beserta rincian peranannya.
8. FORMAT TABEL MARKDOWN:
   - Jika Anda menyajikan data dalam bentuk tabel, gunakan sintaks tabel Markdown standar GitHub Flavored Markdown (baris header | Kolom 1 | Kolom 2 |, baris pemisah |:---|:---|, dan baris data) secara rapi agar sistem dapat merendernya sebagai tabel yang bersih.";

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

        Log::info('[AI Assistant] Sending chat request to NVIDIA API', [
            'model' => $model,
            'user' => $userName,
            'role' => $userRole,
            'timeout' => $timeout,
            'max_tokens' => $maxTokens,
        ]);

        try {
            $response = Http::timeout($timeout)
                ->retry($retryTimes, $retrySleep, function (Throwable $exception) {
                    Log::warning('[AI Assistant] Retrying request due to exception: '.$exception->getMessage());

                    return true;
                }, throw: false)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
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
                $msg = $json['choices'][0]['message'] ?? [];
                $reply = $msg['content'] ?? null;

                if (empty($reply) && ! empty($msg['reasoning_content'])) {
                    $reply = $msg['reasoning_content'];
                }

                if (! empty($reply)) {
                    return [
                        'success' => true,
                        'reply' => trim($reply),
                        'error' => null,
                    ];
                }
            }

            Log::warning('[AI Assistant] HTTP response unsuccessful or empty, activating fallback', [
                'status' => $response ? $response->status() : 'no_response',
                'body' => $response ? ($response->json() ?? $response->body()) : null,
            ]);

            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];

        } catch (ConnectionException $e) {
            Log::warning('[AI Assistant] Connection timeout, activating fallback', ['message' => $e->getMessage()]);

            return [
                'success' => true,
                'reply' => $this->generateLocalFallbackReply($message, $dbContext, $userName, $userRole, $user),
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::error('[AI Assistant] Exception error, activating fallback', ['message' => $e->getMessage()]);

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
            '\bpython\b', '\bjavascript\b', '\btypescript\b', '\bc\+\+\b', '\bjava\b', '\bgolang\b',
            '\brust\b', '\bruby\b', '\bphp script\b', '\bhtml\b', '\bcss\b', '\bsql query\b',
            'coding', 'koding', 'bikin script', 'buatkan kode', 'tuliskan kode', 'tulis kode',
            'buat kode', 'buatkan program', 'bikin program', 'source code', 'fungsi python',
            'code snippet', 'script python', 'pemrograman', 'buat fungsi', 'algoritma prima',
            'rumus fisika', 'turunan matematika', 'integral', 'hitung limit', 'soal kimia',
            'tulis esai sejarah', 'terjemahkan bahasa',
            'resep makanan', 'resep masak', 'lirik lagu', 'chord gitar', 'kunci gitar',
            'ramalan zodiak', 'ramalan cuaca', 'harga bitcoin', 'crypto', 'jadwal bioskop',
            'presiden amerika', 'ibukota negara', 'joke', 'lelucon',
        ];

        foreach ($outOfContextPatterns as $pattern) {
            if (preg_match('/'.$pattern.'/i', $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an Anggota is requesting sensitive records that only Admin or Pengurus may view.
     */
    public function isSensitiveDataRequestForAnggota(string $message, string $userRole): bool
    {
        if (strtolower($userRole) !== 'anggota') {
            return false;
        }

        $lower = mb_strtolower($message);

        $sensitivePatterns = [
            'anggaran', 'budget', 'dana kas', 'biaya proposal', 'proposal rahasia',
            'lpj internal', 'catatan review', 'catatan admin', 'alasan penolakan admin',
            'evaluasi internal', 'semua dokumen proposal', 'semua proposal',
            'tampilkan semua dokumen', 'daftar admin sistem', 'password', 'token rahasia',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
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
        ?User $user = null
    ): string {
        // 1. Check if user asked something completely out of context
        if ($this->isOutOfContext($message)) {
            return 'Maaf, saya adalah AI Assistant yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat melayani permintaan di luar konteks sistem ini (seperti penulisan kode pemrograman atau topik umum di luar aplikasi).';
        }

        $lower = mb_strtolower($message);
        $isAnggota = strtolower($userRole) === 'anggota';
        $isAdmin = strtolower($userRole) === 'admin';
        $isPengurus = strtolower($userRole) === 'pengurus';

        // 2. Sensitive data protection check
        if ($this->isSensitiveDataRequestForAnggota($message, $userRole)) {
            return "Halo {$userName}. Sesuai kebijakan hak akses sistem, dokumen proposal & LPJ internal organisasi bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi serta Administrator. Anda dapat memantau status proposal yang Anda ajukan sendiri melalui menu profil akun Anda.\n\n*(Catatan: Informasi di atas disajikan langsung oleh AI Assistant dari ringkasan database sistem kampus kami secara real-time).*";
        }

        $notice = "\n\n*(Catatan: Informasi di atas disajikan langsung oleh AI Assistant dari ringkasan database sistem kampus kami secara real-time).*";

        // Query: Events / Kegiatan
        if (preg_match('/(kegiatan|acara|event|workshop|seminar|lomba|buka|daftar)/i', $lower)) {
            $events = Event::with('organization')
                ->where('status', 'active')
                ->latest('created_at')
                ->take(5)
                ->get();

            if ($events->isEmpty()) {
                return "Halo {$userName}, saat ini belum ada kegiatan mahasiswa yang sedang aktif dibuka pendaftarannya di database sistem.".$notice;
            }

            $list = [];
            foreach ($events as $e) {
                $org = $e->organization->name ?? 'Umum';
                $quota = $e->quota ? " (Kuota: {$e->quota} peserta)" : '';
                $start = $e->start_time ? $e->start_time->translatedFormat('d M Y, H:i').' WIB' : 'Waktu diumumkan segera';
                $list[] = "• **{$e->title}**\n  - Penyelenggara: {$org}\n  - Waktu: {$start}\n  - Lokasi: {$e->location}{$quota}";
            }

            return "Halo {$userName}! Berikut adalah daftar kegiatan mahasiswa yang sedang aktif dibuka di database sistem:\n\n".implode("\n\n", $list).$notice;
        }

        // Query: Polling / Voting
        if (preg_match('/(polling|voting|vote|suara|jajak)/i', $lower)) {
            $polls = Poll::with('options')
                ->where('status', 'active')
                ->latest('created_at')
                ->take(4)
                ->get();

            if ($polls->isEmpty()) {
                return "Halo {$userName}, saat ini tidak ada polling atau voting yang sedang aktif dibuka di sistem.".$notice;
            }

            $list = [];
            foreach ($polls as $p) {
                $opts = $p->options->pluck('option_text')->implode(', ');
                $deadline = $p->deadline ? $p->deadline->translatedFormat('d M Y, H:i').' WIB' : 'Tanpa batas waktu';
                $list[] = "• **{$p->title}** (Total suara: {$p->totalVotes()})\n  - Batas waktu: {$deadline}\n  - Opsi: {$opts}";
            }

            return "Halo {$userName}! Berikut polling/voting yang saat ini sedang aktif:\n\n".implode("\n\n", $list).$notice;
        }

        // Query: Ruang Diskusi / Topik
        if (preg_match('/(diskusi|forum|topik|komentar)/i', $lower)) {
            $discussions = DiscussionPost::with('user')->latest('created_at')->take(4)->get();

            if ($discussions->isEmpty()) {
                return "Halo {$userName}, belum ada topik diskusi baru di Ruang Diskusi saat ini.".$notice;
            }

            $list = [];
            foreach ($discussions as $d) {
                $author = $d->user->name ?? 'Mahasiswa';
                $list[] = "• **{$d->title}** (oleh {$author}, {$d->comments()->count()} komentar)";
            }

            return "Halo {$userName}! Berikut adalah topik diskusi terkini di Ruang Diskusi kampus:\n\n".implode("\n", $list)."\n\nAnda dapat mengunjungi menu **Ruang Diskusi** untuk ikut berpartisipasi.".$notice;
        }

        // Query: Dokumen / Proposal / LPJ
        if (preg_match('/(dokumen|proposal|lpj|anggaran|pengajuan)/i', $lower)) {
            if ($isAnggota) {
                return "Halo {$userName}. Sesuai kebijakan hak akses sistem, dokumen proposal & LPJ internal organisasi bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi serta Administrator. Anda dapat memantau status proposal yang Anda ajukan sendiri melalui menu profil akun Anda.".$notice;
            }

            $query = Document::latest('created_at');
            if ($isPengurus && $user) {
                $managedOrgIds = $user->organizations()->pluck('organizations.id')->toArray();
                $query->where(function ($q) use ($managedOrgIds, $user) {
                    $q->whereHas('event', fn ($eq) => $eq->whereIn('organization_id', $managedOrgIds))
                        ->orWhere('submitted_by', $user->id);
                });
            }

            $docs = $query->take(5)->get();
            if ($docs->isEmpty()) {
                return "Halo {$userName}, belum ada dokumen proposal atau LPJ yang tercatat untuk hak akses Anda di database.".$notice;
            }

            $list = [];
            foreach ($docs as $d) {
                $notes = ($isAdmin && $d->notes) ? " (Catatan: {$d->notes})" : '';
                $list[] = "• **{$d->title}** (".strtoupper($d->type).' - Status: '.ucfirst($d->status)."){$notes}";
            }

            return "Halo {$userName}! Berikut ringkasan dokumen proposal & LPJ terkini:\n\n".implode("\n", $list).$notice;
        }

        // Query: Organisasi / UKM
        if (preg_match('/(organisasi|ukm|himpunan|daftar ukm)/i', $lower)) {
            $orgs = Organization::take(6)->get();
            if ($orgs->isEmpty()) {
                return "Halo {$userName}, belum ada data organisasi mahasiswa yang terdaftar di database.".$notice;
            }

            $list = [];
            foreach ($orgs as $o) {
                $list[] = "• **{$o->name}**".($o->code ? " [{$o->code}]" : '').': '.Str::limit($o->description, 70);
            }

            return "Halo {$userName}! Berikut adalah beberapa organisasi/UKM yang terdaftar di kampus:\n\n".implode("\n", $list).$notice;
        }

        // Query: Total Anggota (Role 'anggota') spesifik
        if (preg_match('/(total|jumlah|berapa)\s+(banyak\s+)?(anggota|member)/i', $lower) && ! preg_match('/(pengguna|user|akun)/i', $lower)) {
            $anggotaCount = User::where('role', 'anggota')->count();
            $totalUsers = User::count();
            $adminCount = User::where('role', 'admin')->count();
            $pengurusCount = User::where('role', 'pengurus')->count();

            if ($isAdmin || $isPengurus) {
                return "Halo {$userName}! Total **anggota** (pengguna dengan peranan anggota) yang terdaftar di sistem saat ini adalah **{$anggotaCount} orang**.\n\n".
                    "*(Sebagai informasi pelengkap, di sistem ini terdapat juga {$adminCount} Admin dan {$pengurusCount} Pengurus, sehingga total seluruh pengguna adalah {$totalUsers} orang)*".$notice;
            }

            return "Halo {$userName}! Total **anggota** yang terdaftar di sistem saat ini adalah **{$anggotaCount} orang**.".$notice;
        }

        // Query: Total Pengguna / User spesifik
        if (preg_match('/(total|jumlah|berapa)\s+(banyak\s+)?(pengguna|user|akun)/i', $lower) && ! preg_match('/(kegiatan|polling|diskusi|dokumen|organisasi)/i', $lower)) {
            if ($isAnggota) {
                return "Halo {$userName}. Sesuai kebijakan sistem, rincian data administratif akun pengguna hanya dapat diakses oleh Pengurus Organisasi dan Administrator.".$notice;
            }

            $totalUsers = User::count();
            $adminCount = User::where('role', 'admin')->count();
            $pengurusCount = User::where('role', 'pengurus')->count();
            $anggotaCount = User::where('role', 'anggota')->count();

            return "Halo {$userName}! Total **seluruh pengguna** yang terdaftar dalam sistem adalah **{$totalUsers} orang**, dengan rincian peranan:\n\n".
                "• **Anggota**: {$anggotaCount} orang\n".
                "• **Pengurus**: {$pengurusCount} orang\n".
                "• **Admin**: {$adminCount} orang".$notice;
        }

        // Query: Statistik / Ringkasan Sistem Umum
        if (preg_match('/(statistik|ringkasan|overview|total|berapa banyak)/i', $lower)) {
            $totalEvents = Event::where('status', 'active')->count();
            $totalPolls = Poll::where('status', 'active')->count();
            $totalOrgs = Organization::count();
            $totalDiscussions = DiscussionPost::count();

            $reply = "Halo {$userName}! Berikut ringkasan data statistik sistem saat ini:\n\n".
                "• **Organisasi/UKM Terdaftar**: {$totalOrgs} organisasi\n".
                "• **Kegiatan Mahasiswa Aktif**: {$totalEvents} kegiatan dibuka\n".
                "• **Polling/Voting Berjalan**: {$totalPolls} polling aktif\n".
                "• **Topik Ruang Diskusi**: {$totalDiscussions} topik diskusi";

            if ($isAdmin) {
                $totalUsers = User::count();
                $adminCount = User::where('role', 'admin')->count();
                $pengurusCount = User::where('role', 'pengurus')->count();
                $anggotaCount = User::where('role', 'anggota')->count();
                $totalDocs = Document::count();
                $reply .= "\n• **Total Seluruh Pengguna**: {$totalUsers} pengguna (Anggota: {$anggotaCount}, Pengurus: {$pengurusCount}, Admin: {$adminCount})\n".
                    "• **Total Dokumen Proposal/LPJ**: {$totalDocs} dokumen";
            } elseif ($isPengurus && $user) {
                $managedOrgIds = $user->organizations()->pluck('organizations.id')->toArray();
                $orgEvents = Event::whereIn('organization_id', $managedOrgIds)->count();
                $orgDocs = Document::whereHas('event', fn ($q) => $q->whereIn('organization_id', $managedOrgIds))->count();
                $reply .= "\n• **Kegiatan Organisasi Anda**: {$orgEvents} kegiatan\n".
                    "• **Proposal/LPJ Organisasi Anda**: {$orgDocs} dokumen";
            }

            return $reply.$notice;
        }

        // Default General Helpful Answer
        return "Halo {$userName}! Saya adalah **AI Assistant** untuk Sistem Pengelolaan Organisasi Mahasiswa Kampus. Saya terhubung langsung ke database aplikasi untuk memberikan informasi yang akurat dan relevan:\n\n".
            "1. **Kegiatan Mahasiswa**: Tanyakan kegiatan yang sedang aktif dibuka pendaftarannya.\n".
            "2. **Polling & Voting**: Tanyakan polling yang sedang berjalan untuk memberikan suara.\n".
            "3. **Ruang Diskusi**: Cari tahu topik diskusi terbaru seputar kampus.\n".
            "4. **Organisasi/UKM**: Dapatkan daftar organisasi mahasiswa terdaftar.\n".
            '5. **Statistik Sistem**: Tanyakan ringkasan data terkini aplikasi.'.$notice;
    }
}
