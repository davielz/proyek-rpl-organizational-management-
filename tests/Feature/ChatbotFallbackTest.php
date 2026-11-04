<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_activates_fallback_on_nvidia_timeout(): void
    {
        $org = Organization::factory()->create(['name' => 'BEM Fakultas Teknik']);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'title' => 'Seminar Cloud Computing 2026',
            'status' => 'active',
            'location' => 'Aula Gedung C',
        ]);

        $user = User::factory()->create(['name' => 'Ahmad Anggota', 'role' => 'anggota']);

        // Simulate NVIDIA API timeout ConnectionException
        Http::fake([
            'https://integrate.api.nvidia.com/*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 60000 milliseconds');
            },
        ]);

        $service = app(NvidiaChatService::class);
        $result = $service->chat(
            message: 'Kegiatan apa saja yang sedang dibuka?',
            userName: $user->name,
            userRole: ucfirst($user->role),
            user: $user
        );

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertStringContainsString('Seminar Cloud Computing 2026', $result['reply']);
        $this->assertStringContainsString('Aula Gedung C', $result['reply']);
        $this->assertStringContainsString('ringkasan database sistem kampus kami', $result['reply']);
    }

    public function test_chatbot_activates_fallback_on_nvidia_http_error(): void
    {
        $org = Organization::factory()->create();
        $poll = Poll::factory()->create([
            'organization_id' => $org->id,
            'title' => 'Pemilihan Ketua Divisi Humas',
            'status' => 'active',
        ]);
        PollOption::factory()->create([
            'poll_id' => $poll->id,
            'option_text' => 'Kandidat A',
        ]);

        $user = User::factory()->create(['role' => 'anggota']);

        // Simulate NVIDIA API 429 / 500 error
        Http::fake([
            'https://integrate.api.nvidia.com/*' => Http::response([
                'error' => 'Resource exhausted',
            ], 429),
        ]);

        $service = app(NvidiaChatService::class);
        $result = $service->chat(
            message: 'Apakah ada polling yang aktif?',
            userName: $user->name,
            userRole: ucfirst($user->role),
            user: $user
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Pemilihan Ketua Divisi Humas', $result['reply']);
        $this->assertStringContainsString('Kandidat A', $result['reply']);
    }

    public function test_chatbot_fallback_rejects_out_of_context_coding_request(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);

        Http::fake([
            'https://integrate.api.nvidia.com/*' => function () {
                throw new ConnectionException('Timeout');
            },
        ]);

        $service = app(NvidiaChatService::class);
        $result = $service->chat(
            message: 'Buatkan coding python untuk menghitung bilangan prima',
            userName: $user->name,
            userRole: ucfirst($user->role),
            user: $user
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Maaf, saya adalah Asisten AI yang khusus bertugas membantu pengelolaan sistem organisasi mahasiswa di aplikasi ini', $result['reply']);
        $this->assertStringContainsString('Saya tidak dapat membantu permintaan di luar konteks sistem aplikasi ini', $result['reply']);
    }

    public function test_chatbot_fallback_protects_internal_proposals_from_anggota(): void
    {
        $user = User::factory()->create(['name' => 'Siti Anggota', 'role' => 'anggota']);

        Http::fake([
            'https://integrate.api.nvidia.com/*' => function () {
                throw new ConnectionException('Timeout');
            },
        ]);

        $service = app(NvidiaChatService::class);
        $result = $service->chat(
            message: 'Tampilkan semua dokumen proposal dan anggaran organisasi',
            userName: $user->name,
            userRole: ucfirst($user->role),
            user: $user
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('dokumen proposal & LPJ internal organisasi bersifat rahasia dan hanya dapat diakses oleh Pengurus Organisasi serta Administrator', $result['reply']);
    }
}
