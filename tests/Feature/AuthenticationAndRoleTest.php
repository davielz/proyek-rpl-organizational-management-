<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_new_users_can_register_as_anggota(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Baru',
            'email' => 'budibaru@campus.ac.id',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'budibaru@campus.ac.id',
            'role' => 'anggota',
        ]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Diubah',
            'phone' => '08999999999',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Diubah',
            'phone' => '08999999999',
        ]);
    }

    public function test_admin_can_access_admin_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_pengurus_cannot_access_admin_user_management(): void
    {
        $pengurus = User::factory()->pengurus()->create();

        $response = $this->actingAs($pengurus)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_anggota_cannot_access_admin_user_management(): void
    {
        $anggota = User::factory()->anggota()->create();

        $response = $this->actingAs($anggota)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_update_other_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->anggota()->create();

        $response = $this->actingAs($admin)->put("/admin/users/{$targetUser->id}/role", [
            'role' => 'pengurus',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'role' => 'pengurus',
        ]);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put("/admin/users/{$admin->id}/role", [
            'role' => 'anggota',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }
}
