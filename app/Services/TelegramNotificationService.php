<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotificationService
{
    /**
     * Send a notification to Telegram group/admin for a new user registration.
     */
    public function notifyNewUserRegistration(User $user): bool
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (empty($botToken) || empty($chatId)) {
            Log::info('[TelegramNotificationService] Telegram bot token or chat ID is not configured.');
            return false;
        }

        $appName = config('app.name', 'Campus Org System');
        $name = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($user->phone ?? '-', ENT_QUOTES, 'UTF-8');
        $registeredAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $approvalUrl = url('/manage/approvals');

        $message = "🔔 <b>Pemberitahuan Pendaftaran Akun Baru</b>\n\n"
            . "Halo Admin & Pengurus, terdapat pengguna baru yang telah mendaftar di <b>{$appName}</b> dan sedang menunggu persetujuan (ACC):\n\n"
            . "👤 <b>Nama:</b> {$name}\n"
            . "📧 <b>Email:</b> {$email}\n"
            . "📱 <b>No. HP / WhatsApp:</b> {$phone}\n"
            . "🏷 <b>Role:</b> Anggota\n"
            . "📌 <b>Status:</b> ⏳ Menunggu Persetujuan (Pending)\n"
            . "⏰ <b>Waktu:</b> {$registeredAt}\n\n"
            . "Silakan tinjau dan berikan persetujuan (ACC) pada sistem:\n"
            . "👉 <a href=\"{$approvalUrl}\">Buka Halaman Persetujuan Akun</a>";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Send approval notification directly to the user's Telegram chat.
     */
    public function notifyUserAccountApproved(User $user): bool
    {
        // 1. If user doesn't have telegram_chat_id yet, try syncing incoming updates first
        if (empty($user->telegram_chat_id)) {
            $this->syncIncomingUpdates();
            $user->refresh();
        }

        // 2. If still empty, check if we can match any recent interaction by phone
        if (empty($user->telegram_chat_id) && !empty($user->phone)) {
            $matchedUser = $this->findUserByPhoneNumber($user->phone);
            if ($matchedUser && !empty($matchedUser->telegram_chat_id) && empty($user->telegram_chat_id)) {
                $user->telegram_chat_id = $matchedUser->telegram_chat_id;
                $user->save();
            }
        }

        if (empty($user->telegram_chat_id)) {
            Log::info("[TelegramNotificationService] User {$user->name} ({$user->email}) has no telegram_chat_id linked yet.");
            return false;
        }

        $appName = config('app.name', 'Campus Org System');
        $name = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($user->phone ?? '-', ENT_QUOTES, 'UTF-8');
        $loginUrl = url('/login');

        $message = "🎉 <b>Selamat! Akun Anda Telah Disetujui (ACC)</b>\n\n"
            . "Halo <b>{$name}</b>,\n\n"
            . "Kabar baik! Pendaftaran akun Anda di <b>{$appName}</b> telah disetujui (ACC) oleh Admin/Pengurus.\n\n"
            . "📋 <b>Detail Akun Anda:</b>\n"
            . "• <b>Nama:</b> {$name}\n"
            . "• <b>Email:</b> {$email}\n"
            . "• <b>No. HP / WA:</b> {$phone}\n"
            . "• <b>Status:</b> ✅ Aktif\n"
            . "• <b>Role:</b> Anggota\n\n"
            . "Sekarang akun Anda sudah aktif dan dapat langsung digunakan untuk masuk (login) ke sistem organisasi kampus:\n\n"
            . "👉 <a href=\"{$loginUrl}\">Klik di Sini untuk Masuk (Login)</a>";

        $privateSent = $this->sendMessage($message, $user->telegram_chat_id);

        // Also notify the admin/pengurus group
        $groupChatId = config('services.telegram.chat_id');
        if (!empty($groupChatId)) {
            $groupMessage = "✅ <b>Pemberitahuan Akun Disetujui (ACC)</b>\n\n"
                . "Akun pengguna berikut telah disetujui (ACC):\n\n"
                . "👤 <b>Nama:</b> {$name}\n"
                . "📧 <b>Email:</b> {$email}\n"
                . "📱 <b>No. HP:</b> {$phone}\n"
                . "🏷 <b>Role:</b> Anggota\n"
                . "📌 <b>Status:</b> ✅ Aktif\n\n"
                . "📲 <i>Pemberitahuan telah dikirimkan ke Telegram pemilik akun.</i>";

            $this->sendMessage($groupMessage, $groupChatId);
        }

        return $privateSent;
    }

    /**
     * Poll and synchronize incoming Telegram updates (messages, contacts, /start commands).
     */
    public function syncIncomingUpdates(): int
    {
        try {
            $botToken = config('services.telegram.bot_token');
            if (empty($botToken)) {
                return 0;
            }

            $response = Http::timeout(5)->get("https://api.telegram.org/bot{$botToken}/getUpdates");
            if (!$response->successful() || $response->json('ok') !== true) {
                return 0;
            }

            $updates = $response->json('result', []);
            if (empty($updates)) {
                return 0;
            }

            $processedCount = 0;
            $maxUpdateId = 0;

            foreach ($updates as $update) {
                $updateId = $update['update_id'] ?? 0;
                if ($updateId > $maxUpdateId) {
                    $maxUpdateId = $updateId;
                }

                if ($this->processTelegramUpdate($update)) {
                    $processedCount++;
                }
            }

            // Acknowledge updates so they aren't processed repeatedly
            if ($maxUpdateId > 0) {
                Http::timeout(5)->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $maxUpdateId + 1,
                ]);
            }

            return $processedCount;
        } catch (Throwable $e) {
            Log::warning('[TelegramNotificationService] Error syncing incoming updates: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Process an individual Telegram update payload.
     */
    public function processTelegramUpdate(array $update): bool
    {
        $message = $update['message'] ?? null;
        if (!$message) {
            return false;
        }

        $chatId = $message['chat']['id'] ?? null;
        if (!$chatId) {
            return false;
        }

        $senderName = $message['from']['first_name'] ?? 'Pengguna';
        $text = trim($message['text'] ?? '');
        $contact = $message['contact'] ?? null;

        // Case 1: User shared their contact via Telegram button
        if ($contact && !empty($contact['phone_number'])) {
            $phone = $contact['phone_number'];
            $user = $this->findUserByPhoneNumber($phone);

            if ($user) {
                $user->telegram_chat_id = (string) $chatId;
                $user->save();

                $this->sendMessage(
                    "🎉 <b>Nomor Handphone Terverifikasi!</b>\n\nHalo <b>{$user->name}</b>, nomor HP Anda (<code>{$user->phone}</code>) telah berhasil dihubungkan dengan akun Anda di <b>Campus Org System</b>.\n\nAnda akan otomatis menerima notifikasi di chat Telegram ini begitu akun Anda disetujui (ACC) oleh Admin atau Pengurus.",
                    (string) $chatId,
                    'HTML',
                    ['remove_keyboard' => true]
                );

                return true;
            }

            $this->sendMessage(
                "ℹ️ Nomor HP <code>{$phone}</code> belum terdaftar pada sistem Campus Org System. Pastikan nomor yang Anda gunakan di Telegram sama dengan nomor yang Anda masukkan saat mendaftar akun di web.",
                (string) $chatId,
                'HTML',
                ['remove_keyboard' => true]
            );

            return true;
        }

        // Case 2: User started bot via deep-link: /start reg_{userId}
        if (preg_match('/^\/start\s+reg_(\d+)/i', $text, $matches)) {
            $userId = (int) $matches[1];
            $user = User::find($userId);

            if ($user) {
                $user->telegram_chat_id = (string) $chatId;
                $user->save();

                $this->sendMessage(
                    "🎉 <b>Akun Berhasil Terhubung!</b>\n\nHalo <b>{$user->name}</b>, akun Anda (<code>{$user->email}</code>) telah berhasil dihubungkan dengan Bot Telegram Campus Org System.\n\nAnda akan menerima pesan notifikasi di sini begitu akun Anda disetujui (ACC) oleh Admin atau Pengurus.",
                    (string) $chatId,
                    'HTML',
                    ['remove_keyboard' => true]
                );

                return true;
            }
        }

        // Case 3: User typed /start or message containing phone number
        $cleanDigits = preg_replace('/[^\d]/', '', $text);
        if (strlen($cleanDigits) >= 9 && strlen($cleanDigits) <= 15) {
            $user = $this->findUserByPhoneNumber($cleanDigits);
            if ($user) {
                $user->telegram_chat_id = (string) $chatId;
                $user->save();

                $this->sendMessage(
                    "🎉 <b>Nomor Handphone Terverifikasi!</b>\n\nHalo <b>{$user->name}</b>, nomor HP Anda (<code>{$user->phone}</code>) berhasil dihubungkan dengan akun Anda di <b>Campus Org System</b>.\n\nAnda akan menerima notifikasi otomatis saat akun Anda di-ACC.",
                    (string) $chatId,
                    'HTML',
                    ['remove_keyboard' => true]
                );

                return true;
            }
        }

        // Case 4: General /start command
        if (str_starts_with($text, '/start')) {
            $replyMarkup = [
                'keyboard' => [
                    [
                        [
                            'text' => '📱 Bagikan Nomor HP Saya',
                            'request_contact' => true,
                        ],
                    ],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ];

            $this->sendMessage(
                "👋 <b>Halo {$senderName}! Selamat datang di Bot Campus Org System.</b>\n\nAgar bot dapat mengirimkan pesan notifikasi kepada Anda saat akun Anda disetujui (ACC) oleh Admin/Pengurus, silakan tekan tombol <b>\"📱 Bagikan Nomor HP Saya\"</b> di bawah ini:",
                (string) $chatId,
                'HTML',
                $replyMarkup
            );

            return true;
        }

        return false;
    }

    /**
     * Find a user in database by matching normalized phone numbers.
     */
    public function findUserByPhoneNumber(?string $phone): ?User
    {
        if (empty($phone)) {
            return null;
        }

        $normalized = self::normalizePhoneNumber($phone);
        if (empty($normalized)) {
            return null;
        }

        // Extract last 8 digits for quick index search
        $suffix = substr($normalized, -8);

        $candidates = User::where('phone', 'like', "%{$suffix}%")->get();
        foreach ($candidates as $candidate) {
            if (self::normalizePhoneNumber($candidate->phone) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Normalize phone number to standard international format without '+' (e.g. 6281234567890).
     */
    public static function normalizePhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($digits, '08')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }

    /**
     * Send arbitrary message to Telegram chat with optional reply markup.
     */
    public function sendMessage(string $message, ?string $chatId = null, string $parseMode = 'HTML', ?array $replyMarkup = null): bool
    {
        try {
            $botToken = config('services.telegram.bot_token');
            $targetChatId = $chatId ?: config('services.telegram.chat_id');

            if (empty($botToken) || empty($targetChatId)) {
                return false;
            }

            $payload = [
                'chat_id' => $targetChatId,
                'text' => $message,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

            if ($response->successful() && $response->json('ok') === true) {
                return true;
            }

            Log::warning('[TelegramNotificationService] Failed to send Telegram message: ' . $response->body());
            return false;
        } catch (Throwable $e) {
            Log::warning('[TelegramNotificationService] Exception sending Telegram notification: ' . $e->getMessage());
            return false;
        }
    }
}
