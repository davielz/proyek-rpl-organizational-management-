<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.token' => 'DQU7CZDEYuRPop6PSkP8',
            'services.fonnte.admin_phone' => '083809578696',
        ]);
    }

    public function test_whatsapp_notification_is_sent_to_admin_when_new_user_registers(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
                'target' => ['6283809578696'],
            ], 200),
        ]);

        $response = $this->post('/register', [
            'name' => 'Faisal Rahman',
            'email' => 'faisal.rahman@campus.ac.id',
            'phone' => '083865651610',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => 'faisal.rahman@campus.ac.id',
            'phone' => '083865651610',
            'status' => 'pending',
        ]);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), 'api.fonnte.com/send')
                && str_contains($data['target'], '083809578696')
                && str_contains($data['message'], 'Faisal Rahman')
                && str_contains($data['message'], 'faisal.rahman@campus.ac.id')
                && str_contains($data['message'], '083865651610')
                && str_contains($data['message'], 'Menunggu Persetujuan');
        });
    }

    public function test_registration_succeeds_even_if_whatsapp_api_fails(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => false,
                'reason' => 'device disconnected',
            ], 500),
        ]);

        $response = $this->post('/register', [
            'name' => 'Dewi Anggraini',
            'email' => 'dewi.anggraini@campus.ac.id',
            'phone' => '083865651610',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Registration still succeeds and redirects
        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => 'dewi.anggraini@campus.ac.id',
            'status' => 'pending',
        ]);
    }

    public function test_whatsapp_service_formats_and_sends_notification(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
            ], 200),
        ]);

        $user = User::factory()->pending()->create([
            'name' => 'Rangga Wijaya',
            'email' => 'rangga@campus.ac.id',
            'phone' => '083865651610',
        ]);

        $service = app(WhatsAppNotificationService::class);
        $result = $service->notifyNewUserRegistration($user);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($data['message'], 'Rangga Wijaya')
                && str_contains($data['message'], 'rangga@campus.ac.id')
                && str_contains($data['message'], '083865651610')
                && str_contains($data['message'], 'Menunggu Persetujuan');
        });
    }

    public function test_admin_approval_sends_whatsapp_to_user_and_admin(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
            ], 200),
        ]);

        $admin = User::factory()->active()->create([
            'role' => 'admin',
            'phone' => '083809578696',
        ]);

        $user = User::factory()->pending()->create([
            'name' => 'Andi Wijaya',
            'email' => 'andi.wijaya@campus.ac.id',
            'phone' => '083865651610',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.approve', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('active', $user->status);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($data['target'], '083865651610')
                && str_contains($data['message'], 'Selamat! Akun Anda Telah Disetujui (ACC)')
                && str_contains($data['message'], 'Andi Wijaya');
        });
    }

    public function test_pengurus_approval_sends_whatsapp_to_user(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
            ], 200),
        ]);

        $pengurus = User::factory()->active()->create([
            'role' => 'pengurus',
            'phone' => '081299998888',
        ]);

        $user = User::factory()->pending()->create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@campus.ac.id',
            'phone' => '083865651610',
        ]);

        $response = $this->actingAs($pengurus)->post(route('manage.approvals.approve', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('active', $user->status);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($data['target'], '083865651610')
                && str_contains($data['message'], 'Siti Nurhaliza')
                && str_contains($data['message'], 'Disetujui (ACC)');
        });
    }

    public function test_notify_user_without_phone_returns_false_and_does_not_send(): void
    {
        Http::fake();

        $user = User::factory()->active()->create([
            'phone' => null,
        ]);

        $service = app(WhatsAppNotificationService::class);
        $result = $service->notifyUserAccountApproved($user);

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_whatsapp_service_can_get_device_status(): void
    {
        Http::fake([
            'https://api.fonnte.com/device' => Http::response([
                'status' => true,
                'device' => '083809578696',
                'device_status' => 'connect',
                'name' => 'Davi',
            ], 200),
        ]);

        $service = app(WhatsAppNotificationService::class);
        $status = $service->getDeviceStatus();

        $this->assertTrue($status['status']);
        $this->assertEquals('connect', $status['device_status']);
        $this->assertEquals('083809578696', $status['device']);
    }
}

