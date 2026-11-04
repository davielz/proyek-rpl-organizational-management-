<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test 
                            {--message= : Pesan kustom untuk dikirim}
                            {--approval : Uji coba kirim pesan notifikasi ACC/persetujuan akun}
                            {--sync : Sinkronkan update/pesan masuk dari Telegram}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan uji coba notifikasi Telegram atau sinkronisasi update bot';

    /**
     * Execute the console command.
     */
    public function handle(TelegramNotificationService $telegramService): int
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        $this->info('Menguji koneksi Telegram...');
        $this->line("Chat ID: {$chatId}");

        if (empty($botToken)) {
            $this->error('TELEGRAM_BOT_TOKEN belum diatur di .env');
            return Command::FAILURE;
        }

        if ($this->option('sync')) {
            $this->info('Melakukan sinkronisasi update dari bot Telegram...');
            $count = $telegramService->syncIncomingUpdates();
            $this->info("Selesai! {$count} update berhasil diproses.");
            return Command::SUCCESS;
        }

        $customMessage = $this->option('message');

        if ($customMessage) {
            $success = $telegramService->sendMessage($customMessage);
        } elseif ($this->option('approval')) {
            // Simulasi notifikasi persetujuan akun
            $dummyUser = new User([
                'name' => 'Dimas Pratama',
                'email' => 'dimas.pratama@campus.ac.id',
                'phone' => '081234567890',
                'role' => 'anggota',
                'status' => 'active',
                'telegram_chat_id' => $chatId,
            ]);

            $this->info("Mengirim simulasi pesan ACC ke chat ID {$chatId}...");
            $success = $telegramService->notifyUserAccountApproved($dummyUser);
        } else {
            // Simulasi dummy new user
            $dummyUser = new User([
                'name' => 'Calon Anggota (Uji Coba)',
                'email' => 'calon.anggota@campus.ac.id',
                'phone' => '081234567890',
                'role' => 'anggota',
                'status' => 'pending',
            ]);

            $success = $telegramService->notifyNewUserRegistration($dummyUser);
        }

        if ($success) {
            $this->info('Berhasil! Pesan notifikasi telah terkirim ke Telegram.');
            return Command::SUCCESS;
        }

        $this->error('Gagal mengirim pesan ke Telegram. Periksa log atau koneksi internet.');
        return Command::FAILURE;
    }
}
