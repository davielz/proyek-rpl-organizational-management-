<?php

namespace App\Services\Ai;

use App\Models\DiscussionComment;
use App\Models\DiscussionPost;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ChatbotDatabaseContextService
{
    /**
     * Build rich, role-aware, and secure database context for the AI Chatbot prompt.
     */
    public function buildContext(string $userMessage = '', ?User $user = null): string
    {
        try {
            $sections = [];

            $isAdmin = $user && $user->isAdmin();
            $isPengurus = $user && $user->isPengurus();

            // Managed organization IDs for Pengurus / Admin
            $managedOrgIds = [];
            if ($user) {
                if ($isAdmin) {
                    $managedOrgIds = Organization::pluck('id')->toArray();
                } elseif ($isPengurus) {
                    $managedOrgIds = $user->organizations()
                        ->wherePivot('role_in_org', 'pengurus')
                        ->pluck('organizations.id')
                        ->toArray();
                    if (empty($managedOrgIds)) {
                        $managedOrgIds = $user->organizations->pluck('id')->toArray();
                    }
                }
            }

            // 1. General System Statistics (Role-filtered)
            $sections[] = $this->getSystemOverviewSection($user, $isAdmin, $isPengurus, $managedOrgIds);

            // 2. Organizations (Public info)
            $sections[] = $this->getOrganizationsSection();

            // 3. Events (Role-filtered: Anggota only active; Pengurus active + managed org; Admin all)
            $sections[] = $this->getEventsSection($user, $isAdmin, $isPengurus, $managedOrgIds);

            // 4. Polls (Active & accessible polls)
            $sections[] = $this->getPollsSection($user, $isAdmin, $isPengurus);

            // 5. Discussion Forum (Public to members)
            $sections[] = $this->getDiscussionsSection();

            // 6. Documents (Proposals & LPJ) - Sensitive!
            $sections[] = $this->getDocumentsSection($user, $isAdmin, $isPengurus, $managedOrgIds);

            // 7. Event Feedback & Sentiment Summary - Sensitive!
            $sections[] = $this->getFeedbackSummarySection($user, $isAdmin, $isPengurus, $managedOrgIds);

            // 8. User-Specific Context (if user is authenticated)
            if ($user) {
                $sections[] = $this->getUserSpecificSection($user);
            }

            // 9. Members / Anggota (Role 'anggota') list
            $sections[] = $this->getMembersSection($user, $isAdmin, $isPengurus);

            // 10. Organization Officers / Pengurus (Role 'pengurus') list
            $sections[] = $this->getPengurusSection($user, $isAdmin, $isPengurus);

            // 11. Targeted / Keyword Search matching the user's specific query (Role-filtered)
            if (! empty(trim($userMessage))) {
                $searchSection = $this->getTargetedSearchSection($userMessage, $user, $isAdmin, $isPengurus, $managedOrgIds);
                if (! empty($searchSection)) {
                    $sections[] = $searchSection;
                }
            }

            return implode("\n\n", array_filter($sections));
        } catch (Throwable $e) {
            Log::warning('[ChatbotDatabaseContextService] Error building database context: '.$e->getMessage());

            return 'Catatan: Terjadi kendala saat membaca data ringkasan database: '.$e->getMessage();
        }
    }

    /**
     * 1. Overview of total counts and status breakdown in database (Role-filtered).
     */
    protected function getSystemOverviewSection(?User $user, bool $isAdmin, bool $isPengurus, array $managedOrgIds): string
    {
        $totalOrgs = Organization::count();
        $totalDiscussions = DiscussionPost::count();
        $totalComments = DiscussionComment::count();

        $activeEvents = Event::where('status', 'active')->count();
        $totalPolls = Poll::count();
        $activePolls = Poll::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhere('deadline', '>', now());
            })->count();

        // 1. ADMIN: Full system metrics & user/doc breakdown
        if ($isAdmin) {
            $totalUsers = User::count();
            $adminCount = User::where('role', 'admin')->count();
            $pengurusCount = User::where('role', 'pengurus')->count();
            $anggotaCount = User::where('role', 'anggota')->count();

            $totalEvents = Event::count();
            $pendingEvents = Event::whereIn('status', ['draft', 'pending'])->count();
            $completedEvents = Event::where('status', 'completed')->count();

            $closedPolls = max(0, $totalPolls - $activePolls);

            $totalDocs = Document::count();
            $approvedDocs = Document::where('status', 'approved')->count();
            $pendingDocs = Document::whereIn('status', ['submitted', 'pending'])->count();
            $rejectedDocs = Document::where('status', 'rejected')->count();

            $totalFeedbacks = EventFeedback::count();

            return <<<TEXT
[1. RINGKASAN STATISTIK DATABASE SISTEM (KHUSUS ADMINISTRATOR)]
- Total Organisasi Terdaftar: {$totalOrgs} organisasi/UKM
- Total Pengguna: {$totalUsers} orang (Total seluruh akun terdaftar: Anggota: {$anggotaCount}, Pengurus: {$pengurusCount}, Admin: {$adminCount})
- Khusus Total Anggota (Role 'anggota'): {$anggotaCount} orang [PENTING: Jika ditanya "total anggota" atau "jumlah anggota", jawab {$anggotaCount} orang, BUKAN {$totalUsers} orang!]
- Khusus Total Pengurus: {$pengurusCount} orang
- Khusus Total Administrator: {$adminCount} orang
- Total Kegiatan Mahasiswa: {$totalEvents} kegiatan (Aktif/Dibuka: {$activeEvents}, Menunggu/Draft: {$pendingEvents}, Selesai: {$completedEvents})
- Total Polling & Voting: {$totalPolls} polling (Aktif berjalan: {$activePolls}, Ditutup/Selesai: {$closedPolls})
- Total Dokumen Proposal & LPJ: {$totalDocs} dokumen (Disetujui: {$approvedDocs}, Menunggu Review: {$pendingDocs}, Ditolak: {$rejectedDocs})
- Total Ruang Diskusi: {$totalDiscussions} topik diskusi dengan {$totalComments} komentar
- Total Ulasan & Feedback Kegiatan: {$totalFeedbacks} ulasan
TEXT;
        }

        // 2. PENGURUS: Organization management metrics & public stats
        if ($isPengurus) {
            $myEventsCount = Event::whereIn('organization_id', $managedOrgIds)->count();
            $myDocsCount = Document::where(function ($q) use ($managedOrgIds, $user) {
                $q->whereHas('event', fn ($eq) => $eq->whereIn('organization_id', $managedOrgIds))
                    ->orWhere('submitted_by', $user->id);
            })->count();

            return <<<TEXT
[1. RINGKASAN STATISTIK SISTEM (PENGURUS ORGANISASI)]
- Total Organisasi Terdaftar: {$totalOrgs} organisasi/UKM
- Total Kegiatan Mahasiswa Terbuka: {$activeEvents} kegiatan aktif
- Total Polling & Voting Aktif: {$activePolls} polling berjalan
- Total Ruang Diskusi: {$totalDiscussions} topik diskusi
- Ringkasan Organisasi Anda: {$myEventsCount} kegiatan, {$myDocsCount} dokumen proposal/LPJ di bawah naungan organisasi Anda.
TEXT;
        }

        // 3. ANGGOTA / GUEST: General public metrics only
        return <<<TEXT
[1. RINGKASAN STATISTIK SISTEM (PUBLIK)]
- Total Organisasi Terdaftar: {$totalOrgs} organisasi/UKM
- Total Kegiatan Mahasiswa Aktif: {$activeEvents} kegiatan yang sedang dibuka
- Total Polling & Voting Berjalan: {$activePolls} polling aktif
- Total Topik Ruang Diskusi: {$totalDiscussions} topik diskusi aktif
TEXT;
    }

    /**
     * 2. List of registered organizations.
     */
    protected function getOrganizationsSection(): string
    {
        $orgs = Organization::select('id', 'name', 'code', 'description')->take(10)->get();

        if ($orgs->isEmpty()) {
            return "[2. DAFTAR ORGANISASI / UKM]\n(Belum ada organisasi yang terdaftar di database)";
        }

        $lines = ['[2. DAFTAR ORGANISASI / UKM TERDAFTAR DI DATABASE]'];
        foreach ($orgs as $org) {
            $codeStr = $org->code ? " [{$org->code}]" : '';
            $desc = $org->description ? Str::limit(strip_tags($org->description), 100) : 'Tidak ada deskripsi.';
            $lines[] = "- {$org->name}{$codeStr} (ID: {$org->id}): {$desc}";
        }

        return implode("\n", $lines);
    }

    /**
     * 3. Current, active, and recent events (Role-filtered).
     */
    protected function getEventsSection(?User $user, bool $isAdmin, bool $isPengurus, array $managedOrgIds): string
    {
        $query = Event::with(['organization', 'registrations']);

        if ($isAdmin) {
            // Admin sees all events
            $events = $query->latest('created_at')->take(8)->get();
        } elseif ($isPengurus) {
            // Pengurus sees active events + events belonging to their managed org
            $events = $query->where(function ($q) use ($managedOrgIds) {
                $q->where('status', 'active')
                    ->orWhereIn('organization_id', $managedOrgIds);
            })->latest('created_at')->take(8)->get();
        } else {
            // Anggota / Guest ONLY sees active events
            $events = $query->where('status', 'active')->latest('created_at')->take(8)->get();
        }

        if ($events->isEmpty()) {
            return "[3. DATA KEGIATAN MAHASISWA]\n(Belum ada kegiatan yang dapat ditampilkan untuk hak akses Anda)";
        }

        $lines = ['[3. DATA KEGIATAN MAHASISWA (TERKINI & AKTIF)]'];
        foreach ($events as $event) {
            $orgName = $event->organization->name ?? 'Umum / Tanpa Organisasi';
            $registeredCount = $event->registrations->count();
            $quotaStr = $event->quota ? "{$registeredCount}/{$event->quota} peserta" : "{$registeredCount} peserta (Kuota tak terbatas)";
            $startTime = $event->start_time ? $event->start_time->translatedFormat('d F Y, H:i').' WIB' : 'Waktu belum ditentukan';
            $endTime = $event->end_time ? $event->end_time->translatedFormat('d F Y, H:i').' WIB' : '-';
            $descSnippet = Str::limit(strip_tags($event->description ?? ''), 120);

            $lines[] = <<<ITEM
- "{$event->title}" (ID: {$event->id}, Status: {$event->status})
  * Penyelenggara: {$orgName}
  * Waktu: {$startTime} s/d {$endTime}
  * Lokasi: {$event->location}
  * Kuota & Pendaftar: {$quotaStr}
  * Deskripsi: {$descSnippet}
ITEM;
        }

        return implode("\n", $lines);
    }

    /**
     * 4. Active and recent polls with their options and vote counts.
     */
    protected function getPollsSection(?User $user, bool $isAdmin, bool $isPengurus): string
    {
        $query = Poll::with(['organization', 'options' => fn ($q) => $q->withCount('votes')])->latest('created_at');

        if (! $isAdmin && ! $isPengurus) {
            // Anggota sees active polls or recent public polls
            $query->where('status', 'active');
        }

        $polls = $query->take(6)->get();

        if ($polls->isEmpty()) {
            return "[4. DATA POLLING & VOTING ORGANISASI]\n(Belum ada polling yang dapat ditampilkan untuk hak akses Anda)";
        }

        $lines = ['[4. DATA POLLING & VOTING ORGANISASI]'];
        foreach ($polls as $poll) {
            $orgName = $poll->organization->name ?? 'Organisasi Umum';
            $deadlineStr = $poll->deadline ? $poll->deadline->translatedFormat('d F Y, H:i').' WIB' : 'Tanpa batas waktu';
            $isClosed = $poll->isClosed();
            $statusLabel = $isClosed ? 'Ditutup / Selesai' : 'Aktif';
            $totalVotes = $poll->totalVotes();

            $optLines = [];
            foreach ($poll->options as $index => $opt) {
                $votes = $opt->votes_count ?? $opt->votesCount();
                $pct = $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 1) : 0;
                $optLines[] = '    '.($index + 1).". {$opt->option_text} ({$votes} suara, {$pct}%)";
            }
            $optionsBlock = ! empty($optLines) ? implode("\n", $optLines) : '    (Belum ada opsi)';

            $lines[] = <<<ITEM
- Polling: "{$poll->title}" (ID: {$poll->id}, Status: {$statusLabel})
  * Penyelenggara: {$orgName}
  * Batas Waktu: {$deadlineStr}
  * Total Suara Masuk: {$totalVotes} suara
  * Opsi & Perolehan Suara:
{$optionsBlock}
ITEM;
        }

        return implode("\n", $lines);
    }

    /**
     * 5. Recent discussion topics and comments from Ruang Diskusi (Public to members).
     */
    protected function getDiscussionsSection(): string
    {
        $posts = DiscussionPost::with(['user', 'comments.user'])->latest('created_at')->take(6)->get();

        if ($posts->isEmpty()) {
            return "[5. DATA RUANG DISKUSI TERKINI]\n(Belum ada postingan diskusi di Ruang Diskusi)";
        }

        $lines = ['[5. DATA RUANG DISKUSI TERKINI]'];
        foreach ($posts as $post) {
            $authorName = $post->user->name ?? 'Pengguna Anonim';
            $authorRole = $post->user ? ucfirst($post->user->role) : 'Anggota';
            $commentCount = $post->comments->count();
            $createdStr = $post->created_at ? $post->created_at->diffForHumans() : '-';
            $contentSnippet = Str::limit(strip_tags($post->content), 120);

            // Recent 2 comments
            $recentComments = $post->comments->take(2)->map(function ($c) {
                $cAuthor = $c->user->name ?? 'Pengguna';
                $cText = Str::limit(strip_tags($c->content), 80);

                return "    - {$cAuthor}: \"{$cText}\"";
            })->implode("\n");

            $commentsInfo = ! empty($recentComments) ? "\n  * Komentar Terbaru:\n{$recentComments}" : '';

            $lines[] = <<<ITEM
- Diskusi: "{$post->title}" (ID: {$post->id})
  * Penulis: {$authorName} ({$authorRole}) | Dibuat: {$createdStr}
  * Jumlah Komentar: {$commentCount} komentar
  * Isi Diskusi: "{$contentSnippet}"{$commentsInfo}
ITEM;
        }

        return implode("\n", $lines);
    }

    /**
     * 6. Document proposals and LPJ records (Strictly Role-Filtered!).
     * Anggota: Cannot view organization proposals/LPJs.
     * Pengurus: Can only view their own organization's documents.
     * Admin: Full access.
     */
    protected function getDocumentsSection(?User $user, bool $isAdmin, bool $isPengurus, array $managedOrgIds): string
    {
        // 1. ANGGOTA or GUEST: STRICTLY RESTRICTED
        if (! $user || $user->isAnggota()) {
            return "[6. DATA DOKUMEN PROPOSAL & LPJ]\n(Akses dokumen internal proposal & LPJ organisasi dibatasi hanya untuk Pengurus Organisasi dan Administrator sistem. Anggota biasa hanya dapat melihat status proposal yang diajukannya sendiri di bagian Konteks Pengguna).";
        }

        // 2. ADMIN: Full access to all documents
        if ($isAdmin) {
            $docs = Document::with(['event', 'submitter', 'approver'])->latest('created_at')->take(8)->get();
        } else {
            // 3. PENGURUS: Only docs of their managed organization(s) or submitted by them
            $docs = Document::with(['event', 'submitter', 'approver'])
                ->where(function ($q) use ($managedOrgIds, $user) {
                    $q->whereHas('event', fn ($eq) => $eq->whereIn('organization_id', $managedOrgIds))
                        ->orWhere('submitted_by', $user->id);
                })
                ->latest('created_at')
                ->take(8)
                ->get();
        }

        if ($docs->isEmpty()) {
            return "[6. DATA DOKUMEN PROPOSAL & LPJ]\n(Belum ada dokumen proposal atau LPJ yang tercatat untuk hak akses Anda)";
        }

        $lines = ['[6. DATA DOKUMEN PROPOSAL & LPJ]'];
        foreach ($docs as $doc) {
            $eventTitle = $doc->event->title ?? 'Tidak terhubung kegiatan spesifik';
            $submitterName = $doc->submitter->name ?? 'Pengguna';
            $approverName = $doc->approver->name ?? '-';
            $typeLabel = strtoupper($doc->type);
            $statusLabel = ucfirst($doc->status);
            $notesStr = $doc->notes ? ' | Catatan: '.Str::limit(strip_tags($doc->notes), 80) : '';

            $lines[] = <<<ITEM
- "{$doc->title}" (Tipe: {$typeLabel}, Status: {$statusLabel})
  * Kegiatan: "{$eventTitle}"
  * Pengaju: {$submitterName} | Penyetuju/Reviewer: {$approverName}{$notesStr}
ITEM;
        }

        return implode("\n", $lines);
    }

    /**
     * 7. Feedback & Sentiment summary for events (Strictly Role-Filtered!).
     * Anggota: Internal sentiment scores and critique details are restricted.
     * Pengurus: Only for events in their managed organization.
     * Admin: Full access.
     */
    protected function getFeedbackSummarySection(?User $user, bool $isAdmin, bool $isPengurus, array $managedOrgIds): string
    {
        // 1. ANGGOTA or GUEST: Internal sentiment scores and critique details are restricted
        if (! $user || $user->isAnggota()) {
            return "[7. RINGKASAN FEEDBACK & SENTIMEN KEGIATAN]\n(Rincian evaluasi sentimen internal kegiatan dibatasi hanya untuk Pengurus Organisasi dan Administrator).";
        }

        // 2. ADMIN: All feedback
        if ($isAdmin) {
            $feedbacks = EventFeedback::with('event')->latest('created_at')->take(10)->get();
        } else {
            // 3. PENGURUS: Only feedback for events in their managed organization
            $feedbacks = EventFeedback::with('event')
                ->whereHas('event', function ($eq) use ($managedOrgIds) {
                    $eq->whereIn('organization_id', $managedOrgIds);
                })
                ->latest('created_at')
                ->take(10)
                ->get();
        }

        if ($feedbacks->isEmpty()) {
            return "[7. RINGKASAN FEEDBACK & SENTIMEN KEGIATAN]\n(Belum ada ulasan kegiatan yang masuk untuk hak akses Anda)";
        }

        $grouped = $feedbacks->groupBy('event_id');
        $lines = ['[7. RINGKASAN FEEDBACK & SENTIMEN KEGIATAN]'];

        foreach ($grouped as $eventId => $items) {
            $event = $items->first()->event;
            $eventTitle = $event ? $event->title : "Kegiatan #{$eventId}";
            $avgRating = round($items->avg('rating'), 1);
            $count = $items->count();

            $pos = $items->where('sentiment', 'positive')->count();
            $neu = $items->where('sentiment', 'neutral')->count();
            $neg = $items->where('sentiment', 'negative')->count();

            $sampleComment = Str::limit(strip_tags($items->first()->comment ?? ''), 100);

            $lines[] = <<<ITEM
- Kegiatan: "{$eventTitle}" ({$count} ulasan terdaftar)
  * Rata-rata Rating: {$avgRating} / 5.0
  * Sentimen: {$pos} Positif, {$neu} Netral, {$neg} Negatif
  * Contoh Ulasan: "{$sampleComment}"
ITEM;
        }

        return implode("\n", $lines);
    }

    /**
     * 8. User-specific database records for the logged-in user.
     */
    protected function getUserSpecificSection(User $user): string
    {
        $user->loadMissing(['organizations', 'eventRegistrations.event', 'submittedDocuments']);

        $orgs = $user->organizations->map(fn ($o) => "{$o->name} (".($o->pivot->role_in_org ?? 'anggota').')')->implode(', ');
        $orgsStr = ! empty($orgs) ? $orgs : 'Belum bergabung ke organisasi manapun';

        $regs = $user->eventRegistrations->map(function ($r) {
            $eName = $r->event->title ?? "Kegiatan #{$r->event_id}";

            return "{$eName} [Status: {$r->status}]";
        })->implode(', ');
        $regsStr = ! empty($regs) ? $regs : 'Belum mendaftar di kegiatan apapun';

        $docs = $user->submittedDocuments->map(function ($d) {
            return "{$d->title} (".strtoupper($d->type)." - Status: {$d->status})";
        })->implode(', ');
        $docsStr = ! empty($docs) ? $docs : 'Belum pernah mengajukan proposal/LPJ';

        $votedPollIds = PollVote::where('user_id', $user->id)->pluck('poll_id')->unique();
        $votedPolls = Poll::whereIn('id', $votedPollIds)->pluck('title')->implode(', ');
        $pollsStr = ! empty($votedPolls) ? $votedPolls : 'Belum pernah memberikan suara pada polling';

        return <<<TEXT
[8. DATA PENGGUNA YANG SEDANG LOGIN ({$user->name})]
- Nama: {$user->name}
- Email: {$user->email}
- Peranan / Role Sistem: {$user->role}
- Keanggotaan Organisasi: {$orgsStr}
- Kegiatan yang Didaftarkan: {$regsStr}
- Polling yang Sudah Diikuti: {$pollsStr}
- Dokumen yang Diajukan: {$docsStr}
TEXT;
    }

    /**
     * 9. List of registered members (Role 'anggota') in database.
     */
    protected function getMembersSection(?User $user, bool $isAdmin, bool $isPengurus): string
    {
        $query = User::with('organizations')->where('role', 'anggota');

        if (! $isAdmin) {
            $query->where('status', 'active');
        }

        $members = $query->orderBy('name')->take(60)->get();
        $totalCount = User::where('role', 'anggota')->count();

        if ($members->isEmpty()) {
            return "[9. DAFTAR NAMA ANGGOTA MAHASISWA TERDAFTAR (ROLE 'ANGGOTA')]\n(Belum ada data anggota yang terdaftar di database)";
        }

        $lines = [
            "[9. DAFTAR NAMA ANGGOTA MAHASISWA TERDAFTAR (ROLE 'ANGGOTA' - TOTAL: {$totalCount} ORANG)]",
            'PENTING: Gunakan data nama di bawah ini jika pengguna bertanya "Siapa nama-nama anggotanya?", "Sebutkan nama anggotanya", atau pertanyaan seputar daftar nama anggota:',
        ];

        foreach ($members as $index => $m) {
            $num = $index + 1;
            $orgNames = $m->organizations->pluck('name')->implode(', ');
            $orgStr = $orgNames ? " [Organisasi: {$orgNames}]" : '';
            if ($isAdmin) {
                $lines[] = "{$num}. {$m->name}{$orgStr} (Email: {$m->email}, Status: {$m->status})";
            } else {
                $lines[] = "{$num}. {$m->name}{$orgStr}";
            }
        }

        if ($totalCount > $members->count()) {
            $remaining = $totalCount - $members->count();
            $lines[] = "... dan {$remaining} anggota lainnya.";
        }

        return implode("\n", $lines);
    }

    /**
     * 10. List of organization officers (Role 'pengurus') in database.
     */
    protected function getPengurusSection(?User $user, bool $isAdmin, bool $isPengurus): string
    {
        $officers = User::with('organizations')->where('role', 'pengurus')->where('status', 'active')->orderBy('name')->get();

        if ($officers->isEmpty()) {
            return '';
        }

        $lines = [
            "[10. DAFTAR PENGURUS ORGANISASI (ROLE 'PENGURUS' - TOTAL: ".$officers->count().' ORANG)]',
        ];

        foreach ($officers as $index => $p) {
            $num = $index + 1;
            $orgNames = $p->organizations->pluck('name')->implode(', ');
            $orgStr = $orgNames ? " [Organisasi: {$orgNames}]" : '';
            $lines[] = "{$num}. {$p->name}{$orgStr}";
        }

        return implode("\n", $lines);
    }

    /**
     * 11. Targeted search based on words extracted from user message (Role-filtered).
     */
    protected function getTargetedSearchSection(string $query, ?User $user, bool $isAdmin, bool $isPengurus, array $managedOrgIds): string
    {
        $stopWords = [
            'dan', 'di', 'ke', 'dari', 'yang', 'pada', 'untuk', 'dengan', 'ini', 'itu',
            'atau', 'adalah', 'apakah', 'bagaimana', 'kapan', 'dimana', 'siapa', 'mengapa',
            'berapa', 'tolong', 'bisa', 'ada', 'apa', 'halo', 'hai', 'selamat', 'pagi',
            'siang', 'sore', 'malam', 'terima', 'kasih', 'saya', 'kamu', 'anda', 'minta',
            'kegiatan', 'polling', 'diskusi', 'dokumen', 'proposal', 'lpj', 'tentang',
        ];

        // Clean query and extract keywords
        $words = preg_split('/[\s,\.\?\!\:\;\"\']+/u', mb_strtolower($query));
        $keywords = array_filter($words, function ($w) use ($stopWords) {
            return mb_strlen($w) >= 3 && ! in_array($w, $stopWords);
        });

        if (empty($keywords)) {
            return '';
        }

        $results = [];

        // 1. Search Events (Role-filtered)
        $eventQuery = Event::with(['organization', 'registrations'])
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('title', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%")
                        ->orWhere('location', 'like', "%{$word}%");
                }
            });

        if (! $isAdmin) {
            if ($isPengurus) {
                $eventQuery->where(function ($q) use ($managedOrgIds) {
                    $q->where('status', 'active')
                        ->orWhereIn('organization_id', $managedOrgIds);
                });
            } else {
                $eventQuery->where('status', 'active');
            }
        }

        $matchingEvents = $eventQuery->take(3)->get();
        if ($matchingEvents->isNotEmpty()) {
            $eventLines = ['[PENCARIAN KHUSUS: KEGIATAN RELEVAN]'];
            foreach ($matchingEvents as $e) {
                $org = $e->organization->name ?? 'Umum';
                $registered = $e->registrations->count();
                $quota = $e->quota ?: 'Tak Terbatas';
                $eventLines[] = "- \"{$e->title}\" (Status: {$e->status}, Penyelenggara: {$org}, Waktu: {$e->start_time}, Lokasi: {$e->location}, Terdaftar: {$registered}/{$quota})\n  Deskripsi Lengkap: {$e->description}";
            }
            $results[] = implode("\n", $eventLines);
        }

        // 2. Search Polls
        $pollQuery = Poll::with(['options', 'organization'])
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('title', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });

        if (! $isAdmin && ! $isPengurus) {
            $pollQuery->where('status', 'active');
        }

        $matchingPolls = $pollQuery->take(3)->get();
        if ($matchingPolls->isNotEmpty()) {
            $pollLines = ['[PENCARIAN KHUSUS: POLLING RELEVAN]'];
            foreach ($matchingPolls as $p) {
                $opts = $p->options->map(fn ($o) => "{$o->option_text} ({$o->votes_count} suara)")->implode(', ');
                $pollLines[] = "- \"{$p->title}\" (Status: {$p->status}, Deadline: {$p->deadline}, Total Suara: {$p->totalVotes()})\n  Opsi Pilihan: {$opts}";
            }
            $results[] = implode("\n", $pollLines);
        }

        // 3. Search Discussions
        $matchingPosts = DiscussionPost::with(['user', 'comments.user'])
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('title', 'like', "%{$word}%")
                        ->orWhere('content', 'like', "%{$word}%");
                }
            })
            ->take(3)
            ->get();

        if ($matchingPosts->isNotEmpty()) {
            $postLines = ['[PENCARIAN KHUSUS: RUANG DISKUSI RELEVAN]'];
            foreach ($matchingPosts as $p) {
                $author = $p->user->name ?? 'Anonim';
                $postLines[] = "- \"{$p->title}\" oleh {$author} ({$p->created_at->diffForHumans()})\n  Isi: {$p->content}";
                if ($p->comments->isNotEmpty()) {
                    $allComments = $p->comments->take(5)->map(fn ($c) => '    * '.($c->user->name ?? 'User').': '.strip_tags($c->content))->implode("\n");
                    $postLines[] = "  Daftar Komentar:\n{$allComments}";
                }
            }
            $results[] = implode("\n", $postLines);
        }

        // 4. Search Documents (Role-filtered: Anggota cannot search organization documents!)
        if ($isAdmin || $isPengurus) {
            $docQuery = Document::with(['event', 'submitter'])
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('title', 'like', "%{$word}%")
                            ->orWhere('background', 'like', "%{$word}%")
                            ->orWhere('notes', 'like', "%{$word}%");
                    }
                });

            if (! $isAdmin && $isPengurus) {
                $docQuery->where(function ($q) use ($managedOrgIds, $user) {
                    $q->whereHas('event', fn ($eq) => $eq->whereIn('organization_id', $managedOrgIds))
                        ->orWhere('submitted_by', $user->id);
                });
            }

            $matchingDocs = $docQuery->take(3)->get();
            if ($matchingDocs->isNotEmpty()) {
                $docLines = ['[PENCARIAN KHUSUS: DOKUMEN RELEVAN]'];
                foreach ($matchingDocs as $d) {
                    $event = $d->event->title ?? '-';
                    $submitter = $d->submitter->name ?? '-';
                    $docLines[] = "- \"{$d->title}\" (Tipe: ".strtoupper($d->type).", Status: {$d->status}, Terkait Kegiatan: \"{$event}\", Pengaju: {$submitter})\n  Latar Belakang: ".Str::limit(strip_tags($d->background), 200)."\n  Catatan Review: ".($d->notes ?: 'Tidak ada catatan');
                }
                $results[] = implode("\n", $docLines);
            }
        }

        // 5. Search Members / Anggota
        if (preg_match('/(anggota|member|nama|siapa)/i', $query)) {
            $memberQuery = User::with('organizations')->where('role', 'anggota');
            if (! $isAdmin) {
                $memberQuery->where('status', 'active');
            }

            // Check if user mentioned an organization name/code
            $allOrgs = Organization::all();
            foreach ($allOrgs as $org) {
                if (str_contains(mb_strtolower($query), mb_strtolower($org->name)) || ($org->code && str_contains(mb_strtolower($query), mb_strtolower($org->code)))) {
                    $orgMembers = (clone $memberQuery)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $org->id))->get();
                    if ($orgMembers->isNotEmpty()) {
                        $orgLines = ["[PENCARIAN KHUSUS: ANGGOTA ORGANISASI {$org->name}]"];
                        foreach ($orgMembers as $idx => $om) {
                            $orgLines[] = ($idx + 1).". {$om->name}";
                        }
                        $results[] = implode("\n", $orgLines);
                    }
                }
            }

            // Check if matching specific name keywords
            if (! empty($keywords)) {
                $nameMatches = (clone $memberQuery)->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%");
                    }
                })->take(10)->get();

                if ($nameMatches->isNotEmpty()) {
                    $memberLines = ['[PENCARIAN KHUSUS: NAMA ANGGOTA RELEVAN DARI DATABASE]'];
                    foreach ($nameMatches as $idx => $m) {
                        $orgs = $m->organizations->pluck('name')->implode(', ');
                        $orgStr = $orgs ? " [{$orgs}]" : '';
                        $memberLines[] = ($idx + 1).". {$m->name}{$orgStr} (Status: {$m->status})";
                    }
                    $results[] = implode("\n", $memberLines);
                }
            }
        }

        return implode("\n\n", $results);
    }
}
