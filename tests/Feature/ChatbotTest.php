<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

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
                    'reply' => 'Halo! Saya Asisten AI Kampus siap membantu Anda.',
                    'error' => null,
                ]);
        });

        $response = $this->actingAs($user)->postJson('/dashboard/chat', [
            'message' => 'Bagaimana cara membuat proposal?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'reply' => 'Halo! Saya Asisten AI Kampus siap membantu Anda.',
                'error' => null,
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

        $this->mock(NvidiaChatService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->with('Buatkan coding python yang menuliskan nama anda', \Mockery::any(), \Mockery::any(), \Mockery::any())
                ->andReturn([
                    'success' => true,
                    'reply' => 'Maaf, saya adalah Asisten AI yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat membantu permintaan di luar konteks sistem aplikasi ini.',
                    'error' => null,
                ]);
        });

        $response = $this->actingAs($user)->postJson('/dashboard/chat', [
            'message' => 'Buatkan coding python yang menuliskan nama anda',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'reply' => 'Maaf, saya adalah Asisten AI yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini. Saya tidak dapat membantu permintaan di luar konteks sistem aplikasi ini.',
                'error' => null,
            ]);
    }
}
