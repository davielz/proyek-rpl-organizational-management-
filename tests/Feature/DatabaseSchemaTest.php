<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_expected_users_and_roles(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'admin@campus.ac.id', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'pengurus@campus.ac.id', 'role' => 'pengurus']);
        $this->assertDatabaseHas('users', ['email' => 'anggota1@campus.ac.id', 'role' => 'anggota']);
        $this->assertDatabaseHas('users', ['email' => 'anggota2@campus.ac.id', 'role' => 'anggota']);
        $this->assertDatabaseHas('users', ['email' => 'anggota3@campus.ac.id', 'role' => 'anggota']);

        $admin = User::where('email', 'admin@campus.ac.id')->first();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isPengurus());
        $this->assertFalse($admin->isAnggota());
    }

    public function test_organization_and_user_relationships(): void
    {
        $this->seed();

        $hmti = Organization::where('code', 'HMTI')->first();
        $this->assertNotNull($hmti);
        $this->assertGreaterThanOrEqual(4, $hmti->users->count());

        $pengurus = User::where('email', 'pengurus@campus.ac.id')->first();
        $this->assertCount(1, $pengurus->organizations);
        $this->assertEquals('pengurus', $pengurus->organizations->first()->pivot->role_in_org);
    }

    public function test_event_relationships_and_documents(): void
    {
        $this->seed();

        $event = Event::where('slug', 'workshop-laravel-ai-integration-2026')->first();
        $this->assertNotNull($event);
        $this->assertEquals('active', $event->status);
        $this->assertNotNull($event->organization);
        $this->assertNotNull($event->creator);
        $this->assertNotNull($event->proposal);
        $this->assertEquals('approved', $event->proposal->status);

        $this->assertGreaterThanOrEqual(2, $event->participants->count());
        $this->assertGreaterThanOrEqual(2, $event->feedbacks->count());
    }

    public function test_feedback_sentiment_attributes(): void
    {
        $this->seed();

        $feedback = EventFeedback::first();
        $this->assertNotNull($feedback);
        $this->assertNotNull($feedback->event);
        $this->assertNotNull($feedback->user);
        $this->assertIsFloat($feedback->sentiment_score);
    }
}
