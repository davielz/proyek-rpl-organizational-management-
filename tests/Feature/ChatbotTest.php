<?php

namespace Tests\Feature;

use App\Models\AiChatMessage;
use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Prevent real external HTTP calls during testing unless explicitly configured
        config(['ai.api_key' => '']);
    }

    public function test_guest_cannot_access_chatbot_api(): void
    {
        $response = $this->postJson('/dashboard/chat', [
            'message' => 'Halo AI',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_send_message_to_chatbot(): void
    {
        $user = User::factory()->create([
            'role' => 'anggota',
        ]);

        // Mock NvidiaChatService
        $this->mock(NvidiaChatService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'success' => true,
                    'reply' => 'Halo! Saya AI Assistant siap membantu Anda.',
                    'error' => null,
                ]);
        });

        $response = $this->actingAs($user)->postJson('/dashboard/chat', [
            'message' => 'Bagaimana cara membuat proposal?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'reply' => 'Halo! Saya AI Assistant siap membantu Anda.',
                'error' => null,
            ]);

        // Verify messages are persisted in database for this user
        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'Bagaimana cara membuat proposal?',
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => 'Halo! Saya AI Assistant siap membantu Anda.',
        ]);
    }

    public function test_chatbot_validation_fails_on_empty_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/dashboard/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chatbot_service_returns_rejection_for_out_of_context_request(): void
    {
        $user = User::factory()->create();

        $service = app(NvidiaChatService::class);
        $result = $service->chat(
            message: 'Buat kode python yang menuliskan nama saya',
            userName: $user->name,
            userRole: ucfirst($user->role),
            user: $user
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Maaf, saya adalah AI Assistant yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini', $result['reply']);
        $this->assertStringContainsString('Saya tidak dapat melayani permintaan di luar konteks sistem ini', $result['reply']);
    }

    public function test_each_user_account_has_isolated_chat_session_and_cannot_see_other_user_history(): void
    {
        $userA = User::factory()->create(['name' => 'User Ahmad', 'role' => 'anggota']);
        $userB = User::factory()->create(['name' => 'User Budi', 'role' => 'anggota']);

        // User A has existing chat messages in the database
        AiChatMessage::create([
            'user_id' => $userA->id,
            'role' => 'user',
            'content' => 'Pesan rahasia obrolan User Ahmad',
        ]);
        AiChatMessage::create([
            'user_id' => $userA->id,
            'role' => 'assistant',
            'content' => 'Jawaban AI untuk User Ahmad',
        ]);

        // User B has their own messages
        AiChatMessage::create([
            'user_id' => $userB->id,
            'role' => 'user',
            'content' => 'Pertanyaan kegiatan oleh User Budi',
        ]);

        // When User B accesses history endpoint, User B must NOT see User A's messages!
        $responseB = $this->actingAs($userB)->getJson('/dashboard/chat/history');
        $responseB->assertStatus(200)
            ->assertJsonCount(1, 'messages')
            ->assertJsonFragment(['content' => 'Pertanyaan kegiatan oleh User Budi'])
            ->assertJsonMissing(['content' => 'Pesan rahasia obrolan User Ahmad'])
            ->assertJsonMissing(['content' => 'Jawaban AI untuk User Ahmad']);

        // When User A accesses history endpoint, User A only sees User A's messages
        $responseA = $this->actingAs($userA)->getJson('/dashboard/chat/history');
        $responseA->assertStatus(200)
            ->assertJsonCount(2, 'messages')
            ->assertJsonFragment(['content' => 'Pesan rahasia obrolan User Ahmad'])
            ->assertJsonMissing(['content' => 'Pertanyaan kegiatan oleh User Budi']);
    }

    public function test_user_can_clear_their_own_chat_history_without_affecting_other_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        AiChatMessage::create([
            'user_id' => $userA->id,
            'role' => 'user',
            'content' => 'Pesan User A',
        ]);

        AiChatMessage::create([
            'user_id' => $userB->id,
            'role' => 'user',
            'content' => 'Pesan User B',
        ]);

        // User A clears chat
        $response = $this->actingAs($userA)->deleteJson('/dashboard/chat');
        $response->assertStatus(200)->assertJson(['success' => true]);

        // User A messages are deleted
        $this->assertDatabaseMissing('ai_chat_messages', [
            'user_id' => $userA->id,
            'content' => 'Pesan User A',
        ]);

        // User B messages remain completely intact
        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $userB->id,
            'content' => 'Pesan User B',
        ]);
    }

    public function test_anggota_is_refused_sensitive_internal_proposals_and_budget_data(): void
    {
        $anggota = User::factory()->create(['name' => 'Citra Anggota', 'role' => 'anggota']);
        $service = app(NvidiaChatService::class);

        $result = $service->chat(
            message: 'Tampilkan semua rincian anggaran proposal rahasia organisasi',
            userName: $anggota->name,
            userRole: ucfirst($anggota->role),
            user: $anggota
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('dokumen proposal & LPJ internal organisasi bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi serta Administrator', $result['reply']);
    }

    public function test_admin_and_pengurus_can_access_their_relevant_proposals_data(): void
    {
        $org = Organization::factory()->create(['name' => 'Himpunan Mahasiswa']);
        $pengurus = User::factory()->create(['name' => 'Deni Pengurus', 'role' => 'pengurus']);
        $pengurus->organizations()->attach($org->id, ['role_in_org' => 'pengurus']);

        $event = Event::factory()->create(['organization_id' => $org->id, 'title' => 'Dies Natalis Himpunan']);
        Document::factory()->create([
            'event_id' => $event->id,
            'title' => 'Proposal Anggaran Dies Natalis Himpunan',
            'type' => 'proposal',
            'status' => 'submitted',
        ]);

        $service = app(NvidiaChatService::class);

        // Pengurus should NOT be rejected with the sensitive-data refusal
        $result = $service->chat(
            message: 'Bagaimana status dokumen proposal organisasi kita?',
            userName: $pengurus->name,
            userRole: ucfirst($pengurus->role),
            user: $pengurus
        );

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi', $result['reply']);
        $this->assertStringContainsString('Proposal Anggaran Dies Natalis Himpunan', $result['reply']);
    }

    public function test_chatbot_correctly_differentiates_total_anggota_from_total_pengguna(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'pengurus']);
        User::factory()->count(14)->create(['role' => 'anggota']);

        $service = app(NvidiaChatService::class);

        // Query total anggota
        $resAnggota = $service->chat(
            message: 'Berapa total anggota?',
            userName: $admin->name,
            userRole: ucfirst($admin->role),
            user: $admin
        );

        $this->assertTrue($resAnggota['success']);
        // Must mention 14 as total anggota, not 16 as total anggota
        $this->assertStringContainsString('14', $resAnggota['reply']);
        $this->assertStringContainsString('anggota', strtolower($resAnggota['reply']));

        // Query total pengguna
        $resPengguna = $service->chat(
            message: 'Berapa total pengguna?',
            userName: $admin->name,
            userRole: ucfirst($admin->role),
            user: $admin
        );

        $this->assertTrue($resPengguna['success']);
        // Total users is 16 (1 admin + 1 pengurus + 14 anggota)
        $this->assertStringContainsString('16', $resPengguna['reply']);
        $this->assertStringContainsString('pengguna', strtolower($resPengguna['reply']));
    }
}
