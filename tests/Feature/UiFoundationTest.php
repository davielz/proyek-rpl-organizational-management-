<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_ui_showcase(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/ui-showcase');

        $response->assertStatus(200);
        $response->assertSee('UI Foundation &amp; Design System', false);
    }

    public function test_guest_cannot_access_ui_showcase(): void
    {
        $response = $this->get('/ui-showcase');

        $response->assertRedirect('/login');
    }
}
