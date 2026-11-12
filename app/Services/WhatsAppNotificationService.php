<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppNotificationService
{
    /**
     * Send notification to Admin via WhatsApp when a new user registers.
     */
    public function notifyNewUserRegistration(User $user): bool
    {
        $token = config('services.fonnte.token');

        if (empty($token)) {
            Log::info('[WhatsAppNotificationService] Fonnte token is not configured.');

            return false;
        }

        // Collect admin phone numbers
        $adminPhones = $this->resolveAdminPhones();

        if (empty($adminPhones)) {
            Log::warning('[WhatsAppNotificationService] No admin phone numbers configured.');

            return false;
        }

        $appName = config('app.name', 'Campus Org System');
        $name = $user->name;
        $email = $user->email;
        $phone = $user->phone ?? '-';
        $registeredAt = now()->translatedFormat('d F Y, H:i').' WIB';
        $approvalUrl = url('/manage/approvals');

        $message = "🔔 *Pemberitahuan Pendaftaran Akun Baru*\n\n"
            ."Halo Admin, terdapat pengguna baru yang telah mendaftar di *{$appName}* dan sedang menunggu persetujuan (ACC):\n\n"
            ."👤 *Nama:* {$name}\n"
            ."📧 *Email:* {$email}\n"
            ."📱 *No. WhatsApp:* {$phone}\n"
            ."🏷 *Role:* Anggota\n"
            ."📌 *Status:* ⏳ Menunggu Persetujuan (Pending)\n"
            ."⏰ *Waktu:* {$registeredAt}\n\n"
            ."Silakan tinjau dan berikan persetujuan (ACC) pada sistem:\n"
            ."👉 {$approvalUrl}";

        $targets = implode(',', $adminPhones);

        return $this->sendMessage($targets, $message);
    }

    /**
     * Send notification directly to the user's WhatsApp when their account is approved.
     */
    public function notifyUserAccountApproved(User $user): bool
    {
        $token = config('services.fonnte.token');

        if (empty($token)) {
            Log::info('[WhatsAppNotificationService] Fonnte token is not configured.');

            return false;
        }

        if (empty($user->phone)) {
            Log::warning("[WhatsAppNotificationService] User {$user->name} ({$user->email}) does not have a phone number.");

            return false;
        }

        $appName = config('app.name', 'Campus Org System');
        $name = $user->name;
        $email = $user->email;
        $phone = $user->phone;
        $loginUrl = url('/login');

        $userMessage = "🎉 *Selamat! Akun Anda Telah Disetujui (ACC)*\n\n"
            ."Halo *{$name}*,\n\n"
            ."Kabar baik! Pendaftaran akun Anda di *{$appName}* telah disetujui (ACC) oleh Admin.\n\n"
            ."📋 *Detail Akun Anda:*\n"
            ."• *Nama:* {$name}\n"
            ."• *Email:* {$email}\n"
            ."• *No. WhatsApp:* {$phone}\n"
            ."• *Status:* ✅ Aktif\n"
            ."• *Role:* Anggota\n\n"
            ."Sekarang akun Anda sudah aktif dan dapat langsung digunakan untuk masuk (login) ke sistem organisasi kampus:\n\n"
            ."👉 {$loginUrl}\n\n"
            .'Terima kasih telah bergabung!';

        $userSent = $this->sendMessage($user->phone, $userMessage);

        // Also notify admin that the account has been approved
        $adminPhones = $this->resolveAdminPhones();
        if (! empty($adminPhones)) {
            $adminMessage = "✅ *Pemberitahuan Akun Disetujui (ACC)*\n\n"
                ."Akun pengguna berikut telah disetujui (ACC):\n\n"
                ."👤 *Nama:* {$name}\n"
                ."📧 *Email:* {$email}\n"
                ."📱 *No. WhatsApp:* {$phone}\n"
                ."🏷 *Role:* Anggota\n"
                ."📌 *Status:* ✅ Aktif\n\n"
                .'📲 _Pemberitahuan telah dikirimkan ke WhatsApp pemilik akun._';

            $targets = implode(',', $adminPhones);
            $this->sendMessage($targets, $adminMessage);
        }

        return $userSent;
    }

    /**
     * Send a WhatsApp message via Fonnte API.
     */
    public function sendMessage(string $target, string $message): bool
    {
        $token = config('services.fonnte.token');
        $endpoint = config('services.fonnte.endpoint', 'https://api.fonnte.com/send');

        if (empty($token) || empty($target) || empty($message)) {
            Log::warning('[WhatsAppNotificationService] Missing token, target, or message', [
                'target' => $target,
                'has_token' => ! empty($token),
            ]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])
                ->timeout(15)
                ->asForm()
                ->post($endpoint, [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $isOk = ($body['status'] ?? false) === true || ($body['status'] ?? '') === 'true';

                if ($isOk) {
                    Log::info('[WhatsAppNotificationService] WhatsApp message successfully queued/sent', [
                        'target' => $target,
                        'response' => $body,
                    ]);

                    return true;
                }

                Log::warning('[WhatsAppNotificationService] Fonnte API responded with error status', [
                    'target' => $target,
                    'response' => $body,
                ]);

                return false;
            }

            Log::error('[WhatsAppNotificationService] Fonnte HTTP request failed', [
                'target' => $target,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('[WhatsAppNotificationService] Exception while sending WhatsApp message: '.$e->getMessage(), [
                'target' => $target,
            ]);

            return false;
        }
    }

    /**
     * Check Fonnte WhatsApp device connection status.
     *
     * @return array<string, mixed>
     */
    public function getDeviceStatus(): array
    {
        $token = config('services.fonnte.token');

        if (empty($token)) {
            return [
                'status' => false,
                'message' => 'Fonnte token belum diatur.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])
                ->timeout(10)
                ->post('https://api.fonnte.com/device');

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => false,
                'message' => 'HTTP Error: '.$response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve list of admin phone numbers to notify.
     *
     * @return array<int, string>
     */
    protected function resolveAdminPhones(): array
    {
        $phones = [];

        // 1. Configured admin phone in .env/config
        $configAdminPhone = config('services.fonnte.admin_phone');
        if (! empty($configAdminPhone)) {
            $phones[] = $this->cleanPhoneNumber($configAdminPhone);
        }

        // 2. Database admin users with non-empty phone
        try {
            $dbAdminPhones = User::query()
                ->where('role', 'admin')
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->pluck('phone')
                ->toArray();

            foreach ($dbAdminPhones as $p) {
                $cleaned = $this->cleanPhoneNumber($p);
                if (! empty($cleaned)) {
                    $phones[] = $cleaned;
                }
            }
        } catch (Throwable) {
            // DB might not be available during early testing
        }

        return array_values(array_unique(array_filter($phones)));
    }

    /**
     * Clean phone number string.
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}

