<?php

namespace Tests\Feature;

use App\Models\DiscussionComment;
use App\Models\DiscussionPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionRoomTest extends TestCase
{
    use RefreshDatabase;

    protected User $author;
    protected User $otherUser;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['name' => 'Author User', 'role' => 'anggota']);
        $this->otherUser = User::factory()->create(['name' => 'Other User', 'role' => 'anggota']);
        $this->admin = User::factory()->create(['name' => 'Admin User', 'role' => 'admin']);
    }

    // 1. Guest tidak dapat membuat posting.
    public function test_1_guest_cannot_create_discussion_post(): void
    {
        $response = $this->post(route('discussions.store'), [
            'title' => 'Judul Diskusi Guest',
            'content' => 'Isi diskusi dari guest',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('discussion_posts', ['title' => 'Judul Diskusi Guest']);
    }

    // 2. Authenticated user dapat membuat posting.
    public function test_2_authenticated_user_can_create_discussion_post(): void
    {
        $response = $this->actingAs($this->author)->post(route('discussions.store'), [
            'title' => 'Jadwal Kegiatan Minggu Depan',
            'content' => 'Bagaimana pendapat kalian mengenai jadwal kegiatan minggu depan?',
        ]);

        $post = DiscussionPost::where('title', 'Jadwal Kegiatan Minggu Depan')->first();
        $this->assertNotNull($post);

        $response->assertRedirect(route('discussions.show', $post));
        $response->assertSessionHas('success', 'Diskusi berhasil dibuat.');
        $this->assertDatabaseHas('discussion_posts', [
            'user_id' => $this->author->id,
            'title' => 'Jadwal Kegiatan Minggu Depan',
        ]);
    }

    // 3. Title wajib diisi.
    public function test_3_title_is_required(): void
    {
        $response = $this->actingAs($this->author)->post(route('discussions.store'), [
            'title' => '',
            'content' => 'Isi tanpa judul',
        ]);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseMissing('discussion_posts', ['content' => 'Isi tanpa judul']);
    }

    // 4. Content wajib diisi.
    public function test_4_content_is_required(): void
    {
        $response = $this->actingAs($this->author)->post(route('discussions.store'), [
            'title' => 'Judul Tanpa Isi',
            'content' => '',
        ]);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseMissing('discussion_posts', ['title' => 'Judul Tanpa Isi']);
    }

    // 5. User dapat melihat posting.
    public function test_5_user_can_view_discussion_post(): void
    {
        $post = DiscussionPost::factory()->create([
            'user_id' => $this->author->id,
            'title' => 'Diskusi Spesifik',
            'content' => 'Konten detail postingan diskusi',
        ]);

        $response = $this->actingAs($this->otherUser)->get(route('discussions.show', $post));

        $response->assertStatus(200);
        $response->assertSee('Diskusi Spesifik');
        $response->assertSee('Konten detail postingan diskusi');
    }

    // 6. Author dapat edit posting.
    public function test_6_author_can_edit_discussion_post(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)->put(route('discussions.update', $post), [
            'title' => 'Judul Baru yang Diedit',
            'content' => 'Isi postingan baru yang telah diperbarui',
        ]);

        $response->assertRedirect(route('discussions.show', $post));
        $response->assertSessionHas('success', 'Diskusi berhasil diperbarui.');
        $this->assertDatabaseHas('discussion_posts', [
            'id' => $post->id,
            'title' => 'Judul Baru yang Diedit',
        ]);
    }

    // 7. Author dapat delete posting.
    public function test_7_author_can_delete_discussion_post(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)->delete(route('discussions.destroy', $post));

        $response->assertRedirect(route('discussions.index'));
        $response->assertSessionHas('success', 'Diskusi berhasil dihapus.');
        $this->assertDatabaseMissing('discussion_posts', ['id' => $post->id]);
    }

    // 8. User lain tidak dapat edit posting.
    public function test_8_other_user_cannot_edit_discussion_post(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id, 'title' => 'Judul Asli']);

        $response = $this->actingAs($this->otherUser)->put(route('discussions.update', $post), [
            'title' => 'Mencoba Mengubah Judul Orang Lain',
            'content' => 'Isi bajakan',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('discussion_posts', [
            'id' => $post->id,
            'title' => 'Judul Asli',
        ]);
    }

    // 9. User lain tidak dapat delete posting.
    public function test_9_other_user_cannot_delete_discussion_post(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->otherUser)->delete(route('discussions.destroy', $post));

        $response->assertStatus(403);
        $this->assertDatabaseHas('discussion_posts', ['id' => $post->id]);
    }

    // 10. Guest tidak dapat membuat komentar.
    public function test_10_guest_cannot_create_discussion_comment(): void
    {
        $post = DiscussionPost::factory()->create();

        $response = $this->post(route('discussions.comments.store', $post), [
            'content' => 'Komentar dari guest',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('discussion_comments', ['content' => 'Komentar dari guest']);
    }

    // 11. Authenticated user dapat membuat komentar.
    public function test_11_authenticated_user_can_create_discussion_comment(): void
    {
        $post = DiscussionPost::factory()->create();

        $response = $this->actingAs($this->otherUser)->post(route('discussions.comments.store', $post), [
            'content' => 'Menurut saya hari Sabtu lebih cocok.',
        ]);

        $response->assertRedirect(route('discussions.show', $post));
        $response->assertSessionHas('success', 'Komentar berhasil ditambahkan.');
        $this->assertDatabaseHas('discussion_comments', [
            'discussion_post_id' => $post->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Menurut saya hari Sabtu lebih cocok.',
        ]);
    }

    // 12. Comment tidak boleh kosong.
    public function test_12_comment_cannot_be_empty(): void
    {
        $post = DiscussionPost::factory()->create();

        $response = $this->actingAs($this->otherUser)->post(route('discussions.comments.store', $post), [
            'content' => '',
        ]);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseCount('discussion_comments', 0);
    }

    // 13. User dapat melihat komentar.
    public function test_13_user_can_view_discussion_comments(): void
    {
        $post = DiscussionPost::factory()->create();
        $comment = DiscussionComment::factory()->create([
            'discussion_post_id' => $post->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Saya setuju dengan hari Sabtu.',
        ]);

        $response = $this->actingAs($this->author)->get(route('discussions.show', $post));

        $response->assertStatus(200);
        $response->assertSee('Saya setuju dengan hari Sabtu.');
    }

    // 14. Author komentar dapat edit.
    public function test_14_comment_author_can_edit_comment(): void
    {
        $comment = DiscussionComment::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->otherUser)->put(route('discussion-comments.update', $comment), [
            'content' => 'Komentar revisi',
        ]);

        $response->assertRedirect(route('discussions.show', $comment->discussion_post_id));
        $response->assertSessionHas('success', 'Komentar berhasil diperbarui.');
        $this->assertDatabaseHas('discussion_comments', [
            'id' => $comment->id,
            'content' => 'Komentar revisi',
        ]);
    }

    // 15. Author komentar dapat delete.
    public function test_15_comment_author_can_delete_comment(): void
    {
        $comment = DiscussionComment::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->otherUser)->delete(route('discussion-comments.destroy', $comment));

        $response->assertRedirect(route('discussions.show', $comment->discussion_post_id));
        $response->assertSessionHas('success', 'Komentar berhasil dihapus.');
        $this->assertDatabaseMissing('discussion_comments', ['id' => $comment->id]);
    }

    // 16. User lain tidak dapat edit komentar.
    public function test_16_other_user_cannot_edit_comment(): void
    {
        $comment = DiscussionComment::factory()->create([
            'user_id' => $this->otherUser->id,
            'content' => 'Komentar asli',
        ]);

        $response = $this->actingAs($this->author)->put(route('discussion-comments.update', $comment), [
            'content' => 'Percobaan edit bajakan',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('discussion_comments', [
            'id' => $comment->id,
            'content' => 'Komentar asli',
        ]);
    }

    // 17. User lain tidak dapat delete komentar.
    public function test_17_other_user_cannot_delete_comment(): void
    {
        $comment = DiscussionComment::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->author)->delete(route('discussion-comments.destroy', $comment));

        $response->assertStatus(403);
        $this->assertDatabaseHas('discussion_comments', ['id' => $comment->id]);
    }

    // 18 & 19. Menghapus posting menangani komentar dengan benar & tidak ada orphan comment.
    public function test_18_and_19_deleting_post_handles_comments_correctly_no_orphan(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);
        $comment1 = DiscussionComment::factory()->create(['discussion_post_id' => $post->id]);
        $comment2 = DiscussionComment::factory()->create(['discussion_post_id' => $post->id]);

        $this->assertDatabaseCount('discussion_comments', 2);

        $this->actingAs($this->author)->delete(route('discussions.destroy', $post));

        $this->assertDatabaseMissing('discussion_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('discussion_comments', ['id' => $comment1->id]);
        $this->assertDatabaseMissing('discussion_comments', ['id' => $comment2->id]);
        $this->assertDatabaseCount('discussion_comments', 0);
    }

    // 20. Author relationship benar.
    public function test_20_author_relationship_is_correct(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);
        $comment = DiscussionComment::factory()->create([
            'discussion_post_id' => $post->id,
            'user_id' => $this->otherUser->id,
        ]);

        $this->assertEquals($this->author->id, $post->user->id);
        $this->assertEquals($this->otherUser->id, $comment->user->id);
        $this->assertEquals($post->id, $comment->post->id);
    }

    // 21 & 22. User tidak dapat memanipulasi user_id / author posting.
    public function test_21_and_22_user_cannot_manipulate_user_id_or_author(): void
    {
        $response = $this->actingAs($this->otherUser)->post(route('discussions.store'), [
            'title' => 'Mencoba Spoofing Author',
            'content' => 'Isi postingan spoofing',
            'user_id' => $this->author->id, // Attempting to forge author ID
        ]);

        $post = DiscussionPost::where('title', 'Mencoba Spoofing Author')->first();
        $this->assertNotNull($post);
        $this->assertEquals($this->otherUser->id, $post->user_id);
        $this->assertNotEquals($this->author->id, $post->user_id);
    }

    // 23. User tidak dapat mengedit posting melalui direct request.
    public function test_23_user_cannot_edit_posting_via_direct_request(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id, 'title' => 'Judul awal']);

        $response = $this->actingAs($this->otherUser)->call('PUT', route('discussions.update', $post), [
            'title' => 'Judul bajakan direct request',
            'content' => 'Isi bajakan direct request',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Judul awal', $post->fresh()->title);
    }

    // 24. User tidak dapat menghapus posting user lain melalui direct request.
    public function test_24_user_cannot_delete_posting_via_direct_request(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->otherUser)->call('DELETE', route('discussions.destroy', $post));

        $response->assertStatus(403);
        $this->assertDatabaseHas('discussion_posts', ['id' => $post->id]);
    }

    // 25. Empty state.
    public function test_25_ui_empty_state_displayed(): void
    {
        $response = $this->actingAs($this->author)->get(route('discussions.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada diskusi.');

        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);
        $detailResponse = $this->actingAs($this->author)->get(route('discussions.show', $post));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Belum ada komentar.');
        $detailResponse->assertSee('Jadilah yang pertama memberikan pendapat');
    }

    // 26. Validation error state.
    public function test_26_ui_validation_error_state(): void
    {
        $response = $this->actingAs($this->author)->from(route('discussions.create'))->post(route('discussions.store'), [
            'title' => '',
            'content' => '',
        ]);

        $response->assertRedirect(route('discussions.create'));
        $response->assertSessionHasErrors(['title', 'content']);
    }

    // 27. Success state.
    public function test_27_ui_success_state(): void
    {
        $response = $this->actingAs($this->author)->post(route('discussions.store'), [
            'title' => 'Diskusi Berhasil Dibuat Test',
            'content' => 'Isi diskusi test success',
        ]);

        $post = DiscussionPost::where('title', 'Diskusi Berhasil Dibuat Test')->first();
        $this->assertNotNull($post);

        $response->assertRedirect(route('discussions.show', $post));
        $response->assertSessionHas('success', 'Diskusi berhasil dibuat.');
    }

    // 28. Admin moderation powers test.
    public function test_28_admin_can_moderate_and_delete_any_post_and_comment(): void
    {
        $post = DiscussionPost::factory()->create(['user_id' => $this->author->id]);
        $comment = DiscussionComment::factory()->create(['discussion_post_id' => $post->id, 'user_id' => $this->otherUser->id]);

        // Admin deletes comment
        $commentDeleteResponse = $this->actingAs($this->admin)->delete(route('discussion-comments.destroy', $comment));
        $commentDeleteResponse->assertRedirect(route('discussions.show', $post));
        $this->assertDatabaseMissing('discussion_comments', ['id' => $comment->id]);

        // Admin deletes post
        $postDeleteResponse = $this->actingAs($this->admin)->delete(route('discussions.destroy', $post));
        $postDeleteResponse->assertRedirect(route('discussions.index'));
        $this->assertDatabaseMissing('discussion_posts', ['id' => $post->id]);
    }
}
