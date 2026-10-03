<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pengurusA;
    protected User $pengurusB;
    protected User $anggota;
    protected Organization $orgA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pengurusA = User::factory()->create(['role' => 'pengurus']);
        $this->pengurusB = User::factory()->create(['role' => 'pengurus']);
        $this->anggota = User::factory()->create(['role' => 'anggota']);

        $this->orgA = Organization::factory()->create(['name' => 'UKM Multimedia']);
        $this->pengurusA->organizations()->attach($this->orgA->id, ['role_in_org' => 'pengurus']);
    }

    public function test_create_poll_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('polls.create'));
        $response->assertStatus(200);
        $response->assertSee('Buat Polling Baru');
    }

    public function test_admin_can_create_poll(): void
    {
        $response = $this->actingAs($this->admin)->post(route('polls.store'), [
            'title' => 'Pilihan Hari Rapat Rutin Organisasi',
            'description' => 'Mohon pilih hari yang paling fleksibel.',
            'deadline' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'options' => ['Jumat Sore', 'Sabtu Pagi', 'Minggu Sore'],
        ]);

        $response->assertRedirect(route('polls.index'));
        $this->assertDatabaseHas('polls', [
            'title' => 'Pilihan Hari Rapat Rutin Organisasi',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseCount('poll_options', 3);
    }

    public function test_pengurus_can_create_poll(): void
    {
        $response = $this->actingAs($this->pengurusA)->post(route('polls.store'), [
            'title' => 'Pilihan Lokasi Fieldtrip UKM Multimedia',
            'deadline' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'options' => ['Bandung', 'Yogyakarta'],
        ]);

        $response->assertRedirect(route('polls.index'));
        $this->assertDatabaseHas('polls', [
            'title' => 'Pilihan Lokasi Fieldtrip UKM Multimedia',
            'created_by' => $this->pengurusA->id,
        ]);
    }

    public function test_anggota_cannot_create_poll(): void
    {
        $response = $this->actingAs($this->anggota)->post(route('polls.store'), [
            'title' => 'Polling Ilegal dari Anggota',
            'deadline' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'options' => ['Opsi 1', 'Opsi 2'],
        ]);

        $response->assertStatus(403);
    }

    public function test_anggota_can_view_active_polls(): void
    {
        $poll = Poll::factory()->create([
            'created_by' => $this->pengurusA->id,
            'title' => 'Polling Aktif 1',
            'status' => 'active',
            'deadline' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->anggota)->get(route('polls.index'));

        $response->assertStatus(200);
        $response->assertSee('Polling Aktif 1');
    }

    public function test_anggota_can_cast_vote(): void
    {
        $poll = Poll::factory()->create([
            'created_by' => $this->pengurusA->id,
            'status' => 'active',
            'deadline' => now()->addDays(1),
        ]);
        $opt1 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Setuju']);
        $opt2 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Tidak Setuju']);

        $response = $this->actingAs($this->anggota)->post(route('polls.vote', $poll), [
            'option_id' => $opt1->id,
        ]);

        $response->assertRedirect(route('polls.show', $poll));
        $this->assertDatabaseHas('poll_votes', [
            'poll_id' => $poll->id,
            'option_id' => $opt1->id,
            'user_id' => $this->anggota->id,
        ]);
    }

    public function test_anggota_cannot_vote_twice_on_same_poll(): void
    {
        $poll = Poll::factory()->create([
            'created_by' => $this->pengurusA->id,
            'status' => 'active',
            'deadline' => now()->addDays(1),
        ]);
        $opt1 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opsi A']);
        $opt2 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opsi B']);

        // First vote
        PollVote::create([
            'poll_id' => $poll->id,
            'option_id' => $opt1->id,
            'user_id' => $this->anggota->id,
        ]);

        // Second vote attempt
        $response = $this->actingAs($this->anggota)->post(route('polls.vote', $poll), [
            'option_id' => $opt2->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('poll_votes', 1);
    }

    public function test_vote_after_deadline_rejected(): void
    {
        $expiredPoll = Poll::factory()->create([
            'created_by' => $this->pengurusA->id,
            'status' => 'active',
            'deadline' => now()->subHour(), // Expired
        ]);
        $opt = PollOption::create(['poll_id' => $expiredPoll->id, 'option_text' => 'Opsi']);

        $response = $this->actingAs($this->anggota)->post(route('polls.vote', $expiredPoll), [
            'option_id' => $opt->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_vote_on_closed_poll_rejected(): void
    {
        $closedPoll = Poll::factory()->create([
            'created_by' => $this->pengurusA->id,
            'status' => 'closed',
            'deadline' => now()->addDays(1),
        ]);
        $opt = PollOption::create(['poll_id' => $closedPoll->id, 'option_text' => 'Opsi']);

        $response = $this->actingAs($this->anggota)->post(route('polls.vote', $closedPoll), [
            'option_id' => $opt->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_poll_must_have_at_least_two_options(): void
    {
        $response = $this->actingAs($this->pengurusA)->post(route('polls.store'), [
            'title' => 'Polling Cacat (1 Opsi)',
            'deadline' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'options' => ['Hanya 1 Opsi'],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['options']);
    }

    public function test_voting_with_option_from_another_poll_rejected(): void
    {
        $poll1 = Poll::factory()->create(['created_by' => $this->pengurusA->id, 'status' => 'active', 'deadline' => now()->addDay()]);
        $poll2 = Poll::factory()->create(['created_by' => $this->pengurusA->id, 'status' => 'active', 'deadline' => now()->addDay()]);

        $optPoll1 = PollOption::create(['poll_id' => $poll1->id, 'option_text' => 'Opsi Poll 1']);
        $optPoll2 = PollOption::create(['poll_id' => $poll2->id, 'option_text' => 'Opsi Poll 2']);

        // Attempting to submit optPoll2 when voting on poll1
        $response = $this->actingAs($this->anggota)->post(route('polls.vote', $poll1), [
            'option_id' => $optPoll2->id,
        ]);

        $response->assertSessionHasErrors(['option_id']);
        $this->assertDatabaseCount('poll_votes', 0);
    }

    public function test_unauthorized_user_cannot_edit_or_delete_poll(): void
    {
        $poll = Poll::factory()->create(['created_by' => $this->pengurusA->id]);

        // Pengurus B cannot edit Pengurus A's poll
        $responseEdit = $this->actingAs($this->pengurusB)->get(route('polls.edit', $poll));
        $responseEdit->assertStatus(403);

        $responseDelete = $this->actingAs($this->pengurusB)->delete(route('polls.destroy', $poll));
        $responseDelete->assertStatus(403);
    }

    public function test_poll_results_calculated_correctly(): void
    {
        $poll = Poll::factory()->create(['created_by' => $this->pengurusA->id, 'status' => 'active', 'deadline' => now()->addDay()]);
        $optA = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'A']);
        $optB = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'B']);

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        PollVote::create(['poll_id' => $poll->id, 'option_id' => $optA->id, 'user_id' => $u1->id]);
        PollVote::create(['poll_id' => $poll->id, 'option_id' => $optA->id, 'user_id' => $u2->id]);
        PollVote::create(['poll_id' => $poll->id, 'option_id' => $optB->id, 'user_id' => $u3->id]);

        $this->assertEquals(3, $poll->totalVotes());
        $this->assertEquals(2, $optA->votesCount());
        $this->assertEquals(1, $optB->votesCount());
        $this->assertEquals(66.7, $optA->votePercentage(3));
        $this->assertEquals(33.3, $optB->votePercentage(3));
    }

    public function test_empty_state_when_poll_has_no_votes(): void
    {
        $poll = Poll::factory()->create(['created_by' => $this->pengurusA->id, 'status' => 'active', 'deadline' => now()->addDay()]);
        PollOption::create(['poll_id' => $poll->id, 'option_text' => 'A']);

        $response = $this->actingAs($this->anggota)->get(route('polls.show', $poll));

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Suara');
    }

    public function test_options_locked_when_poll_has_votes(): void
    {
        $poll = Poll::factory()->create(['created_by' => $this->pengurusA->id, 'status' => 'active', 'deadline' => now()->addDay()]);
        $optA = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opsi A']);
        $optB = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opsi B']);

        PollVote::create(['poll_id' => $poll->id, 'option_id' => $optA->id, 'user_id' => $this->anggota->id]);

        // Update attempt trying to change options
        $response = $this->actingAs($this->pengurusA)->put(route('polls.update', $poll), [
            'title' => 'Judul Baru',
            'deadline' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'status' => 'active',
            'options' => ['Opsi Dibiarkan Terganti 1', 'Opsi Dibiarkan Terganti 2'],
        ]);

        $response->assertRedirect(route('polls.show', $poll));
        // Verify options were NOT changed
        $this->assertDatabaseHas('poll_options', ['id' => $optA->id, 'option_text' => 'Opsi A']);
        $this->assertDatabaseHas('poll_options', ['id' => $optB->id, 'option_text' => 'Opsi B']);
    }
}
