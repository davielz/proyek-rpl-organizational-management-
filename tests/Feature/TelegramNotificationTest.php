<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_notification_is_sent_when_new_user_registers(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $response = $this->post('/register', [
            'name' => 'Faisal Rahman',
            'email' => 'faisal.rahman@campus.ac.id',
            'phone' => '081298887777',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => 'faisal.rahman@campus.ac.id',
            'status' => 'pending',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] == config('services.telegram.chat_id')
                && str_contains($request['text'], 'Faisal Rahman')
                && str_contains($request['text'], 'faisal.rahman@campus.ac.id');
        });
    }

    public function test_registration_succeeds_even_if_telegram_fails(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Service Unavailable'], 500),
        ]);

        $response = $this->post('/register', [
            'name' => 'Dewi Anggraini',
            'email' => 'dewi.anggraini@campus.ac.id',
            'phone' => '081211119999',
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

    public function test_telegram_service_formats_and_sends_notification(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->pending()->create([
            'name' => 'Rangga Wijaya',
            'email' => 'rangga@campus.ac.id',
            'phone' => '081234567890',
        ]);

        $service = app(TelegramNotificationService::class);
        $result = $service->notifyNewUserRegistration($user);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'Rangga Wijaya')
                && str_contains($request['text'], 'rangga@campus.ac.id')
                && str_contains($request['text'], '081234567890')
                && str_contains($request['text'], 'Menunggu Persetujuan');
        });
    }

    public function test_notify_user_account_approved_sends_message_to_user_chat_and_group(): void
    {
        Http::fake([
            'api.telegram.org/*/getUpdates*' => Http::response(['ok' => true, 'result' => []], 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->active()->create([
            'name' => 'Andi Wijaya',
            'email' => 'andi.wijaya@campus.ac.id',
            'phone' => '081288889999',
            'telegram_chat_id' => '123456789',
        ]);

        $service = app(TelegramNotificationService::class);
        $result = $service->notifyUserAccountApproved($user);

        $this->assertTrue($result);

        // Assert message sent to user
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === '123456789'
                && str_contains($request['text'], 'Telah Disetujui (ACC)')
                && str_contains($request['text'], 'Andi Wijaya');
        });

        // Assert confirmation also sent to group
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] == config('services.telegram.chat_id')
                && str_contains($request['text'], 'Pemberitahuan Akun Disetujui (ACC)');
        });
    }

    public function test_sync_incoming_updates_links_user_by_contact_phone(): void
    {
        $user = User::factory()->pending()->create([
            'phone' => '081233445566',
            'telegram_chat_id' => null,
        ]);

        Http::fake([
            'api.telegram.org/*/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 991,
                        'message' => [
                            'message_id' => 10,
                            'chat' => ['id' => 778899],
                            'from' => ['id' => 778899, 'first_name' => 'Budi'],
                            'contact' => [
                                'phone_number' => '+6281233445566',
                                'first_name' => 'Budi',
                            ],
                        ],
                    ],
                ],
            ], 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $service = app(TelegramNotificationService::class);
        $count = $service->syncIncomingUpdates();

        $this->assertEquals(1, $count);
        $user->refresh();
        $this->assertEquals('778899', $user->telegram_chat_id);
    }

    public function test_sync_incoming_updates_links_user_by_start_reg_parameter(): void
    {
        $user = User::factory()->pending()->create([
            'telegram_chat_id' => null,
        ]);

        Http::fake([
            'api.telegram.org/*/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 992,
                        'message' => [
                            'message_id' => 11,
                            'chat' => ['id' => 556677],
                            'from' => ['id' => 556677, 'first_name' => 'Sari'],
                            'text' => "/start reg_{$user->id}",
                        ],
                    ],
                ],
            ], 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $service = app(TelegramNotificationService::class);
        $count = $service->syncIncomingUpdates();

        $this->assertEquals(1, $count);
        $user->refresh();
        $this->assertEquals('556677', $user->telegram_chat_id);
    }

    public function test_admin_and_pengurus_approval_sends_telegram_to_user(): void
    {
        Http::fake([
            'api.telegram.org/*/getUpdates*' => Http::response(['ok' => true, 'result' => []], 200),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $admin = User::factory()->admin()->create();
        $pendingUser = User::factory()->pending()->create([
            'phone' => '081299990000',
            'telegram_chat_id' => '99887766',
        ]);

        $response = $this->actingAs($admin)->post("/manage/approvals/{$pendingUser->id}/approve");

        $response->assertSessionHas('success');
        $pendingUser->refresh();
        $this->assertEquals('active', $pendingUser->status);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === '99887766'
                && str_contains($request['text'], 'Telah Disetujui (ACC)');
        });
    }
}
