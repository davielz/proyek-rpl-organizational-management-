<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\FallbackAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiDraftGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurusOrgA;

    protected User $pengurusOrgB;

    protected User $anggota;

    protected Organization $orgA;

    protected Organization $orgB;

    protected Event $eventOrgA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pengurusOrgA = User::factory()->create(['role' => 'pengurus']);
        $this->pengurusOrgB = User::factory()->create(['role' => 'pengurus']);
        $this->anggota = User::factory()->create(['role' => 'anggota']);

        $this->orgA = Organization::factory()->create(['name' => 'UKM Cyber Security']);
        $this->orgB = Organization::factory()->create(['name' => 'UKM Seni Foto']);

        $this->pengurusOrgA->organizations()->attach($this->orgA->id, ['role_in_org' => 'pengurus']);
        $this->pengurusOrgB->organizations()->attach($this->orgB->id, ['role_in_org' => 'pengurus']);

        $this->eventOrgA = Event::factory()->create([
            'organization_id' => $this->orgA->id,
            'created_by' => $this->pengurusOrgA->id,
            'title' => 'National Cyber Hackathon 2026',
            'description' => 'Kompetisi peretasan etis dan keamanan siber tingkat nasional.',
            'location' => 'Auditorium Utama Fasilkom',
            'quota' => 100,
        ]);

        Config::set('ai.api_key', 'test-api-key-12345');
        Config::set('ai.mock', false);
    }

    public function test_successful_ai_response()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Proposal Kegiatan: National Cyber Hackathon 2026',
                                        'background' => 'Keamanan siber merupakan elemen krusial di era digital...',
                                        'objectives' => '1. Menguji keahlian mahasiswa.\n2. Menjalin jejaring profesional.',
                                        'budget_plan' => 'Total Rp 5.000.000',
                                        'summary_content' => 'Acara hackathon 24 jam non-stop.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_fallback' => false,
            'data' => [
                'title' => 'Proposal Kegiatan: National Cyber Hackathon 2026',
                'budget_plan' => 'Total Rp 5.000.000',
            ],
        ]);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'test-api-key-12345');
        });
    }

    public function test_ai_timeout_triggers_fallback()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_fallback' => true,
        ]);
        $this->assertNotEmpty($response->json('data.background'));
    }

    public function test_invalid_json_response_triggers_fallback()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'This is NOT a valid JSON object string!'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_fallback' => true,
        ]);
    }

    public function test_api_error_response_triggers_fallback()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Resource exhausted (Rate Limit Exceeded)',
                ],
            ], 429),
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_fallback' => true,
        ]);
    }

    public function test_empty_response_triggers_fallback()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [],
            ], 200),
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_fallback' => true,
        ]);
    }

    public function test_fallback_generator_standalone()
    {
        $fallbackService = new FallbackAiService;
        $proposalData = $fallbackService->generateFallback($this->eventOrgA, 'proposal');
        $lpjData = $fallbackService->generateFallback($this->eventOrgA, 'lpj');

        $this->assertStringContainsString('National Cyber Hackathon 2026', $proposalData['title']);
        $this->assertStringContainsString('ESTIMASI RENCANA ANGGARAN BIAYA', $proposalData['budget_plan']);

        $this->assertStringContainsString('Laporan Pertanggungjawaban', $lpjData['title']);
        $this->assertStringContainsString('LAPORAN REALISASI ANGGARAN BIAYA', $lpjData['budget_plan']);
    }

    public function test_mock_config_triggers_fallback_directly()
    {
        Config::set('ai.mock', true);

        $response = $this->actingAs($this->pengurusOrgA)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'lpj',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_fallback' => true,
        ]);
    }

    public function test_unauthorized_access_prevented()
    {
        // Anggota forbidden (403)
        $responseAnggota = $this->actingAs($this->anggota)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);
        $responseAnggota->assertStatus(403);

        // Pengurus Org B cannot generate draft for Org A event
        $responsePengurusB = $this->actingAs($this->pengurusOrgB)->postJson(route('documents.generate-ai-draft'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
        ]);
        $responsePengurusB->assertStatus(403);
    }
}
