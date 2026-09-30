<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeedbackSentimentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $anggota1;

    protected User $anggota2;

    protected Organization $org;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
        $this->anggota1 = User::factory()->create(['role' => 'anggota']);
        $this->anggota2 = User::factory()->create(['role' => 'anggota']);

        $this->org = Organization::factory()->create(['name' => 'UKM Robotik']);
        $this->pengurus->organizations()->attach($this->org->id, ['role_in_org' => 'pengurus']);

        $this->event = Event::factory()->create([
            'organization_id' => $this->org->id,
            'created_by' => $this->pengurus->id,
            'title' => 'Workshop Robotik 2026',
            'status' => 'completed',
        ]);

        Config::set('ai.api_key', 'test-key-123');
        Config::set('ai.mock', false);
    }

    public function test_user_can_submit_feedback_successfully()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'sentiment' => 'positive',
                                        'sentiment_score' => 0.95,
                                        'topics' => ['pemateri', 'fasilitas'],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->anggota1)->post(route('feedback.store'), [
            'event_id' => $this->event->id,
            'rating' => 5,
            'comment' => 'Acara workshop sangat luar biasa dan pemateriannya sangat bermanfaat!',
        ]);

        $response->assertRedirect(route('events.show', $this->event->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('event_feedbacks', [
            'event_id' => $this->event->id,
            'user_id' => $this->anggota1->id,
            'rating' => 5,
            'sentiment' => 'positive',
            'sentiment_score' => 0.95,
        ]);
    }

    public function test_duplicate_feedback_prevented()
    {
        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->anggota1->id,
            'rating' => 4,
            'comment' => 'Ulasan pertama',
        ]);

        // Second submission attempt by same user for same event should fail validation/authorization!
        $response = $this->actingAs($this->anggota1)->post(route('feedback.store'), [
            'event_id' => $this->event->id,
            'rating' => 5,
            'comment' => 'Mencoba kirim ulasan kedua kali (Harus ditolak)',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthorized_access_to_analytics_dashboard_prevented()
    {
        // Anggota cannot access global feedback analytics dashboard
        $response = $this->actingAs($this->anggota1)->get(route('feedback.index'));
        $response->assertStatus(403);
    }

    public function test_ai_failure_resilience_saves_feedback_anyway()
    {
        // Simulate HTTP Timeout / Connection Error from AI API
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('API Connection timed out');
            },
        ]);

        $response = $this->actingAs($this->anggota1)->post(route('feedback.store'), [
            'event_id' => $this->event->id,
            'rating' => 5,
            'comment' => 'Acara sangat bagus meskipun sistem AI sedang timeout.',
        ]);

        // Feedback MUST STILL BE SAVED!
        $response->assertRedirect(route('events.show', $this->event->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('event_feedbacks', [
            'event_id' => $this->event->id,
            'user_id' => $this->anggota1->id,
            'rating' => 5,
            'sentiment' => 'positive', // Fallback analyzer inferred positive based on rating & keywords
        ]);
    }

    public function test_invalid_ai_response_uses_fallback_analyzer()
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Not a valid JSON string'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->anggota1)->post(route('feedback.store'), [
            'event_id' => $this->event->id,
            'rating' => 1,
            'comment' => 'Sangat kecewa dan pelayanan sangat buruk sekali.',
        ]);

        $response->assertRedirect(route('events.show', $this->event->slug));

        $this->assertDatabaseHas('event_feedbacks', [
            'event_id' => $this->event->id,
            'user_id' => $this->anggota1->id,
            'rating' => 1,
            'sentiment' => 'negative', // Fallback analyzer identified negative
        ]);
    }

    public function test_sentiment_aggregation_in_analytics_dashboard()
    {
        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'rating' => 5,
            'sentiment' => 'positive',
        ]);

        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'rating' => 4,
            'sentiment' => 'positive',
        ]);

        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'rating' => 3,
            'sentiment' => 'neutral',
        ]);

        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'rating' => 1,
            'sentiment' => 'negative',
        ]);

        $response = $this->actingAs($this->pengurus)->get(route('feedback.index'));
        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 4 &&
                $stats['positive_count'] === 2 &&
                $stats['neutral_count'] === 1 &&
                $stats['negative_count'] === 1 &&
                $stats['avg_rating'] == 3.3;
        });
    }

    public function test_admin_can_view_all_feedbacks()
    {
        $response = $this->actingAs($this->admin)->get(route('feedback.index'));
        $response->assertStatus(200);
    }
}
