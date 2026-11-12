<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class TestWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test 
                            {target? : Nomor WhatsApp tujuan (opsional)}
                            {--message= : Pesan kustom untuk dikirim}
                            {--approval : Simulasi kirim pesan notifikasi ACC/persetujuan akun}
                            {--device : Cek status koneksi perangkat WhatsApp di Fonnte}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan uji coba notifikasi WhatsApp via Fonnte API atau cek status perangkat';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppNotificationService $waService): int
    {
        $token = config('services.fonnte.token');
        $adminPhone = config('services.fonnte.admin_phone');

        $this->info('=== Uji Coba Integrasi WhatsApp (Fonnte API) ===');
        $this->line("Admin Phone: {$adminPhone}");

        if (empty($token)) {
            $this->error('FONNTE_TOKEN belum diatur di .env');

            return Command::FAILURE;
        }

        if ($this->option('device')) {
            $this->info('Memeriksa status perangkat WhatsApp Fonnte...');
            $status = $waService->getDeviceStatus();
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $target = $this->argument('target') ?: $adminPhone;
        $customMessage = $this->option('message');

        if ($customMessage) {
            $this->info("Mengirim pesan kustom ke {$target}...");
            $success = $waService->sendMessage($target, $customMessage);
        } elseif ($this->option('approval')) {
            $dummyUser = new User([
                'name' => 'Budi Anggota (Uji Coba)',
                'email' => 'budi.anggota@campus.ac.id',
                'phone' => $target,
                'role' => 'anggota',
                'status' => 'active',
            ]);

            $this->info("Mengirim simulasi pesan persetujuan akun (ACC) ke WhatsApp {$target}...");
            $success = $waService->notifyUserAccountApproved($dummyUser);
        } else {
            $dummyUser = new User([
                'name' => 'Calon Anggota (Uji Coba)',
                'email' => 'calon.anggota@campus.ac.id',
                'phone' => $target,
                'role' => 'anggota',
                'status' => 'pending',
            ]);

            $this->info("Mengirim simulasi pesan pendaftaran baru ke admin ({$adminPhone})...");
            $success = $waService->notifyNewUserRegistration($dummyUser);
        }

        if ($success) {
            $this->info('Berhasil! Pesan notifikasi WhatsApp telah terkirim via Fonnte.');

            return Command::SUCCESS;
        }

        $this->error('Gagal mengirim pesan WhatsApp. Periksa log atau koneksi Fonnte.');

        return Command::FAILURE;
    }
}

