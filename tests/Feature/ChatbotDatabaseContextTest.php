<?php

namespace Tests\Feature;

use App\Models\DiscussionComment;
use App\Models\DiscussionPost;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use App\Services\Ai\ChatbotDatabaseContextService;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotDatabaseContextTest extends TestCase
{
    use RefreshDatabase;

    protected ChatbotDatabaseContextService $contextService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextService = app(ChatbotDatabaseContextService::class);
    }

    public function test_it_generates_system_overview_statistics_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'pengurus']);
        User::factory()->create(['role' => 'anggota']);

        Organization::factory()->count(2)->create();
        Event::factory()->count(3)->create(['status' => 'active']);
        Poll::factory()->count(2)->create(['status' => 'active', 'deadline' => now()->addDays(2)]);
        DiscussionPost::factory()->count(4)->create();

        $context = $this->contextService->buildContext(user: $admin);

        $this->assertStringContainsString('[1. RINGKASAN STATISTIK DATABASE SISTEM (KHUSUS ADMINISTRATOR)]', $context);
        $this->assertStringContainsString('Total Organisasi Terdaftar: '.Organization::count().' organisasi/UKM', $context);
        $this->assertStringContainsString('Total Pengguna: '.User::count().' orang', $context);
        $this->assertStringContainsString('Total Kegiatan Mahasiswa: '.Event::count().' kegiatan (Aktif/Dibuka: 3', $context);
        $this->assertStringContainsString('Total Polling & Voting: 2 polling (Aktif berjalan: 2', $context);
        $this->assertStringContainsString('Total Ruang Diskusi: 4 topik diskusi', $context);
    }

    public function test_anggota_does_not_see_sensitive_system_overview_or_internal_proposals(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);
        $admin = User::factory()->create(['role' => 'admin']);

        $event = Event::factory()->create(['title' => 'Dies Natalis Kampus', 'status' => 'active']);
        Document::factory()->create([
            'event_id' => $event->id,
            'title' => 'Proposal Rahasia Anggaran Dies Natalis',
            'budget_plan' => 'Total Anggaran Rp 50.000.000',
            'status' => 'rejected',
            'notes' => 'Anggaran konsumsi terlalu besar.',
        ]);

        EventFeedback::factory()->create([
            'event_id' => $event->id,
            'rating' => 2,
            'comment' => 'Acara molor dan konsumsi kurang.',
            'sentiment' => 'negative',
        ]);

        $context = $this->contextService->buildContext(user: $anggota);

        // Anggota receives public stats only
        $this->assertStringContainsString('[1. RINGKASAN STATISTIK SISTEM (PUBLIK)]', $context);
        $this->assertStringNotContainsString('Total Pengguna:', $context);
        $this->assertStringNotContainsString('Admin: 1', $context);

        // Anggota does not see internal proposals or rejected notes
        $this->assertStringContainsString('Akses dokumen internal proposal & LPJ organisasi dibatasi hanya untuk Pengurus Organisasi dan Administrator', $context);
        $this->assertStringNotContainsString('Proposal Rahasia Anggaran Dies Natalis', $context);
        $this->assertStringNotContainsString('Anggaran konsumsi terlalu besar', $context);

        // Anggota does not see internal sentiment breakdown
        $this->assertStringContainsString('Rincian evaluasi sentimen internal kegiatan dibatasi hanya untuk Pengurus Organisasi dan Administrator', $context);
        $this->assertStringNotContainsString('Acara molor dan konsumsi kurang', $context);
    }

    public function test_pengurus_can_see_only_their_organization_proposals_and_feedbacks(): void
    {
        $orgA = Organization::factory()->create(['name' => 'UKM Musik']);
        $orgB = Organization::factory()->create(['name' => 'UKM Robotika']);

        $pengurusA = User::factory()->create(['role' => 'pengurus']);
        $pengurusA->organizations()->attach($orgA->id, ['role_in_org' => 'pengurus']);

        $eventA = Event::factory()->create(['organization_id' => $orgA->id, 'title' => 'Konser UKM Musik', 'status' => 'active']);
        $eventB = Event::factory()->create(['organization_id' => $orgB->id, 'title' => 'Lomba UKM Robotika', 'status' => 'active']);

        $docA = Document::factory()->create([
            'event_id' => $eventA->id,
            'title' => 'Proposal Konser UKM Musik',
            'status' => 'approved',
        ]);

        $docB = Document::factory()->create([
            'event_id' => $eventB->id,
            'title' => 'Proposal Rahasia UKM Robotika',
            'status' => 'submitted',
        ]);

        $context = $this->contextService->buildContext(user: $pengurusA);

        $this->assertStringContainsString('[1. RINGKASAN STATISTIK SISTEM (PENGURUS ORGANISASI)]', $context);
        $this->assertStringContainsString('Proposal Konser UKM Musik', $context);
        $this->assertStringNotContainsString('Proposal Rahasia UKM Robotika', $context);
    }

    public function test_it_reads_and_formats_events_from_database(): void
    {
        $org = Organization::factory()->create(['name' => 'UKM Robotika']);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'title' => 'Kompetisi Robot Nasional 2026',
            'location' => 'Gedung Olahraga Kampus',
            'quota' => 100,
            'status' => 'active',
            'description' => 'Ajang bergengsi kompetisi robotika antar mahasiswa.',
        ]);

        $user = User::factory()->create();
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);

        $context = $this->contextService->buildContext();

        $this->assertStringContainsString('[3. DATA KEGIATAN MAHASISWA (TERKINI & AKTIF)]', $context);
        $this->assertStringContainsString('Kompetisi Robot Nasional 2026', $context);
        $this->assertStringContainsString('Penyelenggara: UKM Robotika', $context);
        $this->assertStringContainsString('Lokasi: Gedung Olahraga Kampus', $context);
        $this->assertStringContainsString('1/100 peserta', $context);
    }

    public function test_it_reads_and_formats_polls_and_options_from_database(): void
    {
        $poll = Poll::factory()->create([
            'title' => 'Pemilihan Ketua Divisi Media',
            'status' => 'active',
            'deadline' => now()->addDays(5),
        ]);

        $opt1 = PollOption::create([
            'poll_id' => $poll->id,
            'option_text' => 'Kandidat A (Andi)',
        ]);

        $opt2 = PollOption::create([
            'poll_id' => $poll->id,
            'option_text' => 'Kandidat B (Bunga)',
        ]);

        $voter = User::factory()->create();
        PollVote::create([
            'poll_id' => $poll->id,
            'option_id' => $opt1->id,
            'user_id' => $voter->id,
        ]);

        $context = $this->contextService->buildContext();

        $this->assertStringContainsString('[4. DATA POLLING & VOTING ORGANISASI]', $context);
        $this->assertStringContainsString('Pemilihan Ketua Divisi Media', $context);
        $this->assertStringContainsString('Kandidat A (Andi) (1 suara, 100%)', $context);
        $this->assertStringContainsString('Kandidat B (Bunga) (0 suara, 0%)', $context);
        $this->assertStringContainsString('Total Suara Masuk: 1 suara', $context);
    }

    public function test_it_reads_and_formats_discussions_and_comments_from_database(): void
    {
        $author = User::factory()->create(['name' => 'Budi Penulis', 'role' => 'pengurus']);
        $post = DiscussionPost::factory()->create([
            'user_id' => $author->id,
            'title' => 'Pembahasan Anggaran Acara Dies Natalis',
            'content' => 'Bagaimana pembagian pos anggaran untuk konsumsi dan pubdok?',
        ]);

        $commenter = User::factory()->create(['name' => 'Siti Pengomentar']);
        DiscussionComment::factory()->create([
            'discussion_post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Menurut saya pos konsumsi perlu diprioritaskan 40%.',
        ]);

        $context = $this->contextService->buildContext();

        $this->assertStringContainsString('[5. DATA RUANG DISKUSI TERKINI]', $context);
        $this->assertStringContainsString('Pembahasan Anggaran Acara Dies Natalis', $context);
        $this->assertStringContainsString('Penulis: Budi Penulis (Pengurus)', $context);
        $this->assertStringContainsString('Siti Pengomentar: "Menurut saya pos konsumsi perlu diprioritaskan 40%."', $context);
    }

    public function test_admin_can_read_and_format_all_proposals_and_documents_from_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $submitter = User::factory()->create(['name' => 'Doni Submitter']);
        $event = Event::factory()->create(['title' => 'Pelatihan Public Speaking 2026']);

        Document::factory()->create([
            'event_id' => $event->id,
            'submitted_by' => $submitter->id,
            'title' => 'Proposal Pelatihan Public Speaking',
            'type' => 'proposal',
            'status' => 'approved',
            'notes' => 'Disetujui penuh oleh kemahasiswaan.',
        ]);

        $context = $this->contextService->buildContext(user: $admin);

        $this->assertStringContainsString('[6. DATA DOKUMEN PROPOSAL & LPJ]', $context);
        $this->assertStringContainsString('Proposal Pelatihan Public Speaking', $context);
        $this->assertStringContainsString('Status: Approved', $context);
        $this->assertStringContainsString('Pengaju: Doni Submitter', $context);
        $this->assertStringContainsString('Disetujui penuh oleh kemahasiswaan', $context);
    }

    public function test_it_provides_user_specific_database_context(): void
    {
        $user = User::factory()->create(['name' => 'Mahasiswa Aktif', 'email' => 'aktif@campus.ac.id', 'role' => 'anggota']);
        $org = Organization::factory()->create(['name' => 'UKM Musik']);
        $user->organizations()->attach($org->id, ['role_in_org' => 'pengurus']);

        $event = Event::factory()->create(['title' => 'Konser Amal Tahunan']);
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);

        $context = $this->contextService->buildContext(user: $user);

        $this->assertStringContainsString('[8. DATA PENGGUNA YANG SEDANG LOGIN (Mahasiswa Aktif)]', $context);
        $this->assertStringContainsString('UKM Musik (pengurus)', $context);
        $this->assertStringContainsString('Konser Amal Tahunan [Status: registered]', $context);
    }

    public function test_it_includes_registered_members_list_in_database_context(): void
    {
        User::factory()->create(['name' => 'Dimas Anggota A', 'role' => 'anggota', 'status' => 'active']);
        User::factory()->create(['name' => 'Siti Anggota B', 'role' => 'anggota', 'status' => 'active']);
        User::factory()->create(['name' => 'Budi Admin Sistem', 'role' => 'admin', 'status' => 'active']);

        $context = $this->contextService->buildContext();

        $this->assertStringContainsString("[9. DAFTAR NAMA ANGGOTA MAHASISWA TERDAFTAR (ROLE 'ANGGOTA'", $context);
        $this->assertStringContainsString('Dimas Anggota A', $context);
        $this->assertStringContainsString('Siti Anggota B', $context);
        $this->assertStringNotContainsString('Budi Admin Sistem', $context);
    }

    public function test_it_performs_targeted_search_on_specific_keywords(): void
    {
        Event::factory()->create([
            'title' => 'Hackathon Web 3.0 Kampus',
            'description' => 'Lomba koding 24 jam bertema blockchain dan decentralization.',
            'location' => 'Auditorium Lantai 2',
            'status' => 'active',
        ]);

        $context = $this->contextService->buildContext(userMessage: 'Apakah ada info mengenai hackathon blockchain?');

        $this->assertStringContainsString('[PENCARIAN KHUSUS: KEGIATAN RELEVAN]', $context);
        $this->assertStringContainsString('Hackathon Web 3.0 Kampus', $context);
        $this->assertStringContainsString('blockchain', $context);
    }

    public function test_nvidia_chat_service_passes_database_context_and_role_policy(): void
    {
        Http::fake([
            'https://integrate.api.nvidia.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Berdasarkan data database, saat ini terdapat kegiatan Lomba Robotika.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['name' => 'Budi Admin', 'role' => 'admin']);
        Event::factory()->create([
            'title' => 'Lomba Robotika Nasional',
            'status' => 'active',
        ]);

        config(['ai.api_key' => 'fake-test-key']);

        $service = app(NvidiaChatService::class);
        $response = $service->chat(
            message: 'Apa saja kegiatan yang tersedia?',
            userName: 'Budi Admin',
            userRole: 'Admin',
            user: $admin
        );

        $this->assertTrue($response['success']);
        $this->assertEquals('Berdasarkan data database, saat ini terdapat kegiatan Lomba Robotika.', $response['reply']);

        Http::assertSent(function ($request) {
            $systemMsg = collect($request['messages'])->firstWhere('role', 'system')['content'] ?? '';

            return str_contains($systemMsg, 'AKSES DATABASE SISTEM (REAL-TIME DATA)')
                && str_contains($systemMsg, 'KEBIJAKAN HAK AKSES & KEAMANAN INFORMASI')
                && str_contains($systemMsg, 'PENGGUNA MEMILIKI HAK AKSES ADMINISTRATOR')
                && str_contains($systemMsg, 'Lomba Robotika Nasional');
        });
    }
}
