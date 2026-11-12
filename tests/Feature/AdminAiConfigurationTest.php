<?php

namespace Tests\Feature;

use App\Models\AiConfiguration;
use App\Models\User;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $anggota;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->pengurus = User::factory()->create([
            'role' => 'pengurus',
            'status' => 'active',
        ]);

        $this->anggota = User::factory()->create([
            'role' => 'anggota',
            'status' => 'active',
        ]);

        // Update or seed basic configurations for testing
        AiConfiguration::updateOrCreate(
            ['provider' => 'nvidia'],
            [
                'name' => 'NVIDIA NIM Test',
                'api_key' => 'nvapi-testkey-1234567890',
                'model' => 'nvidia/nemotron-3.5-bolt-e4b',
                'base_url' => 'https://integrate.api.nvidia.com/v1',
                'timeout' => 60,
                'max_tokens' => 1000,
                'temperature' => 0.70,
                'is_active' => true,
                'is_default' => true,
            ]
        );

        AiConfiguration::updateOrCreate(
            ['provider' => 'gemini'],
            [
                'name' => 'Google Gemini Test',
                'api_key' => 'AIzaSyTestKey123456789',
                'model' => 'gemini-1.5-flash',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'timeout' => 60,
                'max_tokens' => 1000,
                'temperature' => 0.70,
                'is_active' => false,
                'is_default' => true,
            ]
        );
    }

    public function test_guest_cannot_access_ai_configurations(): void
    {
        $response = $this->get(route('admin.ai.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_ai_configurations(): void
    {
        $responseAnggota = $this->actingAs($this->anggota)->get(route('admin.ai.index'));
        $responseAnggota->assertForbidden();

        $responsePengurus = $this->actingAs($this->pengurus)->get(route('admin.ai.index'));
        $responsePengurus->assertForbidden();
    }

    public function test_admin_can_view_ai_configurations_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ai.index'));

        $response->assertOk();
        $response->assertSee('Konfigurasi AI Assistant');
        $response->assertSee('NVIDIA NIM Test');
        $response->assertSee('Google Gemini Test');
        $response->assertSee('Provider AI Sedang Aktif');
    }

    public function test_admin_can_store_new_custom_ai_provider(): void
    {
        $payload = [
            'name' => 'Groq Llama 3',
            'provider' => 'custom',
            'api_key' => 'gsk_test123456789',
            'model' => 'llama-3.1-70b-versatile',
            'base_url' => 'https://api.groq.com/openai/v1',
            'timeout' => 45,
            'max_tokens' => 1500,
            'temperature' => 0.5,
            'description' => 'Fast inference provider with Groq LPU',
            'is_active' => '0',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.ai.store'), $payload);

        $response->assertRedirect(route('admin.ai.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ai_configurations', [
            'name' => 'Groq Llama 3',
            'provider' => 'custom',
            'model' => 'llama-3.1-70b-versatile',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_update_ai_configuration_without_overwriting_api_key_when_empty(): void
    {
        $config = AiConfiguration::where('provider', 'nvidia')->first();
        $originalApiKey = $config->api_key;

        $response = $this->actingAs($this->admin)->put(route('admin.ai.update', $config), [
            'name' => 'NVIDIA NIM Updated',
            'provider' => 'nvidia',
            'api_key' => '', // Left empty, should NOT overwrite original API key
            'model' => 'nvidia/nemotron-3-ultra-550b-a55b',
            'base_url' => 'https://integrate.api.nvidia.com/v1',
            'timeout' => 90,
            'max_tokens' => 1200,
            'temperature' => 0.6,
        ]);

        $response->assertRedirect(route('admin.ai.index'));
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertEquals('NVIDIA NIM Updated', $config->name);
        $this->assertEquals('nvidia/nemotron-3-ultra-550b-a55b', $config->model);
        $this->assertEquals($originalApiKey, $config->api_key);
        $this->assertEquals(90, $config->timeout);
    }

    public function test_admin_can_activate_different_ai_provider(): void
    {
        $nvidiaConfig = AiConfiguration::where('provider', 'nvidia')->first();
        $geminiConfig = AiConfiguration::where('provider', 'gemini')->first();

        $this->assertTrue($nvidiaConfig->is_active);
        $this->assertFalse($geminiConfig->is_active);

        $response = $this->actingAs($this->admin)->post(route('admin.ai.activate', $geminiConfig));

        $response->assertRedirect(route('admin.ai.index'));
        $response->assertSessionHas('success');

        $nvidiaConfig->refresh();
        $geminiConfig->refresh();

        $this->assertFalse($nvidiaConfig->is_active);
        $this->assertTrue($geminiConfig->is_active);
    }

    public function test_admin_cannot_delete_active_ai_provider(): void
    {
        $activeConfig = AiConfiguration::where('is_active', true)->first();

        $response = $this->actingAs($this->admin)->delete(route('admin.ai.destroy', $activeConfig));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ai_configurations', ['id' => $activeConfig->id]);
    }

    public function test_admin_cannot_delete_default_ai_provider(): void
    {
        $defaultConfig = AiConfiguration::where('is_default', true)
            ->where('is_active', false)
            ->first();

        $response = $this->actingAs($this->admin)->delete(route('admin.ai.destroy', $defaultConfig));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ai_configurations', ['id' => $defaultConfig->id]);
    }

    public function test_admin_can_delete_custom_inactive_ai_provider(): void
    {
        $customConfig = AiConfiguration::create([
            'name' => 'Temporary Custom AI',
            'provider' => 'custom',
            'api_key' => 'test-key',
            'model' => 'custom-model',
            'base_url' => 'https://custom.endpoint.com/v1',
            'is_active' => false,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ai.destroy', $customConfig));

        $response->assertRedirect(route('admin.ai.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('ai_configurations', ['id' => $customConfig->id]);
    }

    public function test_admin_can_test_connection_via_ajax(): void
    {
        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Koneksi berhasil.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $config = AiConfiguration::where('provider', 'nvidia')->first();

        $response = $this->actingAs($this->admin)->postJson(route('admin.ai.test', $config));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'reply' => 'Koneksi berhasil.',
        ]);
    }

    public function test_test_connection_returns_error_when_api_key_empty(): void
    {
        $emptyKeyConfig = AiConfiguration::create([
            'name' => 'Empty Key Provider',
            'provider' => 'nvidia',
            'api_key' => null,
            'model' => 'nvidia/test',
            'base_url' => 'https://integrate.api.nvidia.com/v1',
            'is_active' => false,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.ai.test', $emptyKeyConfig));

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'message' => 'Kunci API (API Key) belum diisi untuk provider ini.',
        ]);
    }

    public function test_chat_service_uses_dynamic_active_provider_from_database(): void
    {
        $geminiConfig = AiConfiguration::where('provider', 'gemini')->first();
        $geminiConfig->activate();

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Halo! Ini respon dari model Google Gemini.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $chatService = app(NvidiaChatService::class);
        $result = $chatService->chat('Kapan kegiatan seminar kampus dibuka?', [], 'Budi', 'Anggota', $this->anggota);

        $this->assertTrue($result['success']);
        $this->assertEquals('Halo! Ini respon dari model Google Gemini.', $result['reply']);
    }
}
