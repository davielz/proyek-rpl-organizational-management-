<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Users (1 Admin, 1 Pengurus, 3 Anggota)
        $admin = User::updateOrCreate(
            ['email' => 'admin@campus.ac.id'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '081234567890',
            ]
        );

        $pengurus = User::updateOrCreate(
            ['email' => 'pengurus@campus.ac.id'],
            [
                'name' => 'Budi Pratama (Pengurus HMTI)',
                'password' => Hash::make('password'),
                'role' => 'pengurus',
                'phone' => '081298765432',
            ]
        );

        $anggota1 = User::updateOrCreate(
            ['email' => 'anggota1@campus.ac.id'],
            [
                'name' => 'Siti Aminah',
                'password' => Hash::make('password'),
                'role' => 'anggota',
                'phone' => '081311112222',
            ]
        );

        $anggota2 = User::updateOrCreate(
            ['email' => 'anggota2@campus.ac.id'],
            [
                'name' => 'Rudi Hermawan',
                'password' => Hash::make('password'),
                'role' => 'anggota',
                'phone' => '081333334444',
            ]
        );

        $anggota3 = User::updateOrCreate(
            ['email' => 'anggota3@campus.ac.id'],
            [
                'name' => 'Dewi Lestari',
                'password' => Hash::make('password'),
                'role' => 'anggota',
                'phone' => '081355556666',
            ]
        );

        // 2. Seed Organizations
        $hmti = Organization::updateOrCreate(
            ['code' => 'HMTI'],
            [
                'name' => 'Himpunan Mahasiswa Teknik Informatika',
                'description' => 'Organisasi kemahasiswaan jurusan Teknik Informatika.',
            ]
        );

        $bem = Organization::updateOrCreate(
            ['code' => 'BEM'],
            [
                'name' => 'Badan Eksekutif Mahasiswa',
                'description' => 'Organisasi pemerintahan mahasiswa tingkat universitas.',
            ]
        );

        // 3. Organization Memberships
        $hmti->users()->syncWithoutDetaching([
            $pengurus->id => ['role_in_org' => 'pengurus'],
            $anggota1->id => ['role_in_org' => 'anggota'],
            $anggota2->id => ['role_in_org' => 'anggota'],
            $anggota3->id => ['role_in_org' => 'anggota'],
        ]);

        // 4. Sample Events
        $event1 = Event::updateOrCreate(
            ['slug' => 'workshop-laravel-ai-integration-2026'],
            [
                'organization_id' => $hmti->id,
                'created_by' => $pengurus->id,
                'title' => 'Workshop Laravel & AI Integration 2026',
                'description' => 'Workshop praktis membangun aplikasi Laravel berbasis AI untuk mahasiswa.',
                'location' => 'Lab Komputer 3, Gedung Utama',
                'start_time' => now()->addDays(3)->setHour(9)->setMinute(0),
                'end_time' => now()->addDays(3)->setHour(15)->setMinute(0),
                'quota' => 50,
                'status' => 'active',
            ]
        );

        $event2 = Event::updateOrCreate(
            ['slug' => 'national-tech-seminar-2026'],
            [
                'organization_id' => $hmti->id,
                'created_by' => $pengurus->id,
                'title' => 'National Tech Seminar & Expo 2026',
                'description' => 'Seminar nasional membahas tren AI, Cloud Native, dan Vibe Coding.',
                'location' => 'Auditorium Kampus',
                'start_time' => now()->addDays(14)->setHour(8)->setMinute(0),
                'end_time' => now()->addDays(14)->setHour(16)->setMinute(0),
                'quota' => 200,
                'status' => 'pending',
            ]
        );

        // 5. Sample Proposals & LPJ
        Document::updateOrCreate(
            ['event_id' => $event1->id, 'type' => 'proposal'],
            [
                'submitted_by' => $pengurus->id,
                'approved_by' => $admin->id,
                'title' => 'Proposal Workshop Laravel & AI Integration 2026',
                'background' => 'Perkembangan kecerdasan buatan dalam pengembangan perangkat lunak menuntut mahasiswa IT untuk menguasai Laravel modern.',
                'objectives' => 'Meningkatkan keterampilan coding mahasiswa dalam membangun aplikasi web profesional.',
                'budget_plan' => "1. Honor Pemateri: Rp 1.500.000\n2. Konsumsi 50 Peserta: Rp 1.000.000\n3. Sertifikat: Rp 300.000",
                'status' => 'approved',
                'notes' => 'Proposal disetujui, silakan laksanakan kegiatan.',
            ]
        );

        Document::updateOrCreate(
            ['event_id' => $event2->id, 'type' => 'proposal'],
            [
                'submitted_by' => $pengurus->id,
                'title' => 'Proposal National Tech Seminar & Expo 2026',
                'background' => 'Seminar nasional untuk memperkenalkan mahasiswa pada teknologi terdepan.',
                'objectives' => 'Menghubungkan mahasiswa dengan praktisi industri IT.',
                'budget_plan' => "1. Sewa Auditorium: Rp 3.000.000\n2. Guest Speaker: Rp 5.000.000",
                'status' => 'submitted',
            ]
        );

        // 6. Registrations & Attendance
        EventRegistration::updateOrCreate(
            ['event_id' => $event1->id, 'user_id' => $anggota1->id],
            [
                'status' => 'attended',
                'registered_at' => now()->subDays(2),
                'attended_at' => now()->addDays(3)->setHour(9)->setMinute(15),
            ]
        );

        EventRegistration::updateOrCreate(
            ['event_id' => $event1->id, 'user_id' => $anggota2->id],
            [
                'status' => 'attended',
                'registered_at' => now()->subDays(1),
                'attended_at' => now()->addDays(3)->setHour(9)->setMinute(20),
            ]
        );

        EventRegistration::updateOrCreate(
            ['event_id' => $event1->id, 'user_id' => $anggota3->id],
            [
                'status' => 'registered',
                'registered_at' => now(),
            ]
        );

        // 7. Feedbacks & Sentiment Analysis
        EventFeedback::updateOrCreate(
            ['event_id' => $event1->id, 'user_id' => $anggota1->id],
            [
                'rating' => 5,
                'comment' => 'Materi workshop sangat bermanfaat dan pemateri menyampaikan dengan sangat jelas!',
                'sentiment' => 'positive',
                'sentiment_score' => 0.96,
            ]
        );

        EventFeedback::updateOrCreate(
            ['event_id' => $event1->id, 'user_id' => $anggota2->id],
            [
                'rating' => 4,
                'comment' => 'Sangat bagus dan menambah wawasan, hanya saja waktu tanya jawab perlu diperpanjang.',
                'sentiment' => 'neutral',
                'sentiment_score' => 0.70,
            ]
        );
    }
}
