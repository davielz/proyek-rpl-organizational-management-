<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_view_event_catalog(): void
    {
        Event::factory()->create(['status' => 'active']);

        $response = $this->get('/events');
        $response->assertStatus(200);
    }

    public function test_public_user_can_view_active_event_detail(): void
    {
        $event = Event::factory()->create(['status' => 'active']);

        $response = $this->get("/events/{$event->slug}");
        $response->assertStatus(200);
    }

    public function test_pengurus_can_create_event_for_their_organization(): void
    {
        $pengurus = User::factory()->pengurus()->create();
        $org = Organization::factory()->create();
        $pengurus->organizations()->attach($org->id, ['role_in_org' => 'pengurus']);

        $response = $this->actingAs($pengurus)->post('/manage/events', [
            'organization_id' => $org->id,
            'title' => 'Web Dev Workshop 2026',
            'description' => 'Workshop pengembangan web modern.',
            'location' => 'Lab Komputer 1',
            'start_time' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(5)->addHours(3)->format('Y-m-d\TH:i'),
            'quota' => 30,
            'status' => 'active',
        ]);

        $response->assertRedirect('/manage/events');
        $this->assertDatabaseHas('events', [
            'organization_id' => $org->id,
            'title' => 'Web Dev Workshop 2026',
            'created_by' => $pengurus->id,
        ]);
    }

    public function test_pengurus_cannot_update_other_organization_event(): void
    {
        $pengurus = User::factory()->pengurus()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $pengurus->organizations()->attach($org1->id, ['role_in_org' => 'pengurus']);
        $otherEvent = Event::factory()->create(['organization_id' => $org2->id]);

        $response = $this->actingAs($pengurus)->put("/manage/events/{$otherEvent->id}", [
            'organization_id' => $org2->id,
            'title' => 'Judul Diubah Tanpa Izin',
            'description' => 'Deskripsi.',
            'location' => 'Lokasi.',
            'start_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->addHours(2)->format('Y-m-d\TH:i'),
            'quota' => 20,
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_event(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin)->put("/manage/events/{$event->id}", [
            'organization_id' => $event->organization_id,
            'title' => 'Judul Diubah Oleh Admin',
            'description' => $event->description,
            'location' => $event->location,
            'start_time' => $event->start_time->format('Y-m-d\TH:i'),
            'end_time' => $event->end_time->format('Y-m-d\TH:i'),
            'quota' => 50,
            'status' => 'active',
        ]);

        $response->assertRedirect('/manage/events');
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Judul Diubah Oleh Admin',
        ]);
    }

    public function test_anggota_can_register_for_active_event(): void
    {
        $anggota = User::factory()->anggota()->create();
        $event = Event::factory()->create([
            'status' => 'active',
            'quota' => 50,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);

        $response = $this->actingAs($anggota)->post("/events/{$event->slug}/register");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $anggota->id,
            'status' => 'registered',
        ]);
    }

    public function test_anggota_cannot_register_twice_for_same_event(): void
    {
        $anggota = User::factory()->anggota()->create();
        $event = Event::factory()->create([
            'status' => 'active',
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $anggota->id,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($anggota)->post("/events/{$event->slug}/register");

        $response->assertStatus(403);
    }

    public function test_anggota_cannot_register_if_quota_is_full(): void
    {
        $anggota = User::factory()->anggota()->create();
        $event = Event::factory()->create([
            'status' => 'active',
            'quota' => 1,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);

        // Fill quota
        $otherUser = User::factory()->create();
        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $otherUser->id,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($anggota)->post("/events/{$event->slug}/register");

        $response->assertStatus(403);
    }

    public function test_anggota_can_cancel_registration_before_event(): void
    {
        $anggota = User::factory()->anggota()->create();
        $event = Event::factory()->create([
            'status' => 'active',
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHours(2),
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $anggota->id,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($anggota)->post("/events/{$event->slug}/cancel");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $anggota->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_anggota_cannot_access_manage_events(): void
    {
        $anggota = User::factory()->anggota()->create();

        $response = $this->actingAs($anggota)->get('/manage/events');
        $response->assertStatus(403);
    }
}
