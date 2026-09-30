<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $anggota;

    protected Organization $org;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
        $this->anggota = User::factory()->create(['role' => 'anggota']);

        $this->org = Organization::factory()->create(['name' => 'UKM Media & Pers']);
        $this->pengurus->organizations()->attach($this->org->id, ['role_in_org' => 'pengurus']);

        $this->event = Event::factory()->create([
            'organization_id' => $this->org->id,
            'created_by' => $this->pengurus->id,
            'title' => 'Pelatihan Jurnalistik Kampus 2026',
            'status' => 'active',
            'start_time' => now()->addDays(5),
        ]);
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_dashboard_displays_real_database_stats()
    {
        Document::factory()->create([
            'event_id' => $this->event->id,
            'submitted_by' => $this->pengurus->id,
            'status' => 'submitted',
        ]);

        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->anggota->id,
            'rating' => 5,
            'sentiment' => 'positive',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_members'] >= 1 &&
                $stats['total_events'] >= 1 &&
                $stats['pending_reviews_count'] === 1 &&
                $stats['avg_rating'] == 5.0;
        });
    }

    public function test_pengurus_dashboard_displays_managed_organization_stats()
    {
        Document::factory()->create([
            'event_id' => $this->event->id,
            'submitted_by' => $this->pengurus->id,
            'status' => 'submitted',
        ]);

        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->anggota->id,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($this->pengurus)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.pengurus');
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_events'] >= 1 &&
                $stats['total_registrations'] >= 1 &&
                $stats['pending_documents'] === 1;
        });
    }

    public function test_anggota_dashboard_displays_registrations_and_upcoming_events()
    {
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->anggota->id,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($this->anggota)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.anggota');
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_followed'] === 1 &&
                $stats['registered'] === 1;
        });
        $response->assertViewHas('pendingFeedbackRegistrations', function ($list) {
            return $list->count() === 1;
        });
    }
}
