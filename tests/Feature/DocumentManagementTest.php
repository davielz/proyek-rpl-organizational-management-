<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurusOrgA;

    protected User $pengurusOrgB;

    protected User $anggota;

    protected Organization $orgA;

    protected Organization $orgB;

    protected Event $eventOrgA;

    protected Event $eventOrgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->pengurusOrgA = User::factory()->create(['role' => 'pengurus']);
        $this->pengurusOrgB = User::factory()->create(['role' => 'pengurus']);
        $this->anggota = User::factory()->create(['role' => 'anggota']);

        $this->orgA = Organization::factory()->create(['name' => 'UKM Robotik']);
        $this->orgB = Organization::factory()->create(['name' => 'BEM Fasilkom']);

        $this->pengurusOrgA->organizations()->attach($this->orgA->id, ['role_in_org' => 'pengurus']);
        $this->pengurusOrgB->organizations()->attach($this->orgB->id, ['role_in_org' => 'pengurus']);

        $this->eventOrgA = Event::factory()->create([
            'organization_id' => $this->orgA->id,
            'created_by' => $this->pengurusOrgA->id,
            'title' => 'Workshop Robotik 2026',
        ]);

        $this->eventOrgB = Event::factory()->create([
            'organization_id' => $this->orgB->id,
            'created_by' => $this->pengurusOrgB->id,
            'title' => 'Seminar Teknologi 2026',
        ]);
    }

    public function test_pengurus_can_view_document_index()
    {
        $response = $this->actingAs($this->pengurusOrgA)->get(route('documents.index'));
        $response->assertStatus(200);
    }

    public function test_anggota_cannot_access_document_module()
    {
        $response = $this->actingAs($this->anggota)->get(route('documents.index'));
        $response->assertStatus(403);
    }

    public function test_pengurus_can_create_document_draft()
    {
        $response = $this->actingAs($this->pengurusOrgA)->post(route('documents.store'), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
            'title' => 'Proposal Workshop Robotik 2026',
            'background' => 'Latar belakang pengembangan teknologi robotik mahasiswa.',
            'objectives' => 'Melatih dasar pemrograman sensor dan mekanik robot.',
            'budget_plan' => 'Total Rp 2.500.000',
            'summary_content' => 'Acara berlangsung 1 hari full workshop.',
        ]);

        $this->assertDatabaseHas('documents', [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
            'title' => 'Proposal Workshop Robotik 2026',
            'status' => 'draft',
            'submitted_by' => $this->pengurusOrgA->id,
        ]);

        $document = Document::where('title', 'Proposal Workshop Robotik 2026')->first();
        $response->assertRedirect(route('documents.show', $document));
    }

    public function test_pengurus_cannot_create_document_for_other_organization()
    {
        $response = $this->actingAs($this->pengurusOrgA)->post(route('documents.store'), [
            'event_id' => $this->eventOrgB->id, // Org B event!
            'type' => 'proposal',
            'title' => 'Illegal Proposal Creation',
            'background' => 'Attempting to create document for Org B',
            'objectives' => 'Should fail with 403 forbidden',
        ]);

        $response->assertStatus(403);
    }

    public function test_pengurus_can_update_draft_document()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'draft',
            'title' => 'Draft Awal Proposal',
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->put(route('documents.update', $document), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
            'title' => 'Proposal Robotik Revisi Draf',
            'background' => 'Latar belakang yang diperbarui dengan rinci.',
            'objectives' => 'Tujuan yang telah diperjelas.',
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'Proposal Robotik Revisi Draf',
        ]);
    }

    public function test_pengurus_can_submit_document_for_review()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->post(route('documents.submit', $document));

        $response->assertRedirect(route('documents.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'submitted',
        ]);
    }

    public function test_unauthorized_modification_locked_after_submission()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'submitted',
            'title' => 'Dokumen Sudah Diajukan',
        ]);

        // Attempting to edit submitted document should be blocked by Policy!
        $response = $this->actingAs($this->pengurusOrgA)->put(route('documents.update', $document), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
            'title' => 'Mencoba Edit Dokumen Diajukan',
            'background' => 'This should be blocked by server-side policy.',
            'objectives' => 'Forbidden edit attempt.',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_review_and_approve_document()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('documents.review', $document), [
            'action' => 'approve',
            'notes' => 'Dokumen lengkap dan sesuai dengan kriteria kampus.',
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'notes' => 'Dokumen lengkap dan sesuai dengan kriteria kampus.',
        ]);
    }

    public function test_admin_can_request_revision_with_notes()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('documents.review', $document), [
            'action' => 'request_revision',
            'notes' => 'Mohon perjelas rincian konsumsi dan rundown acara.',
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'revision_requested',
            'notes' => 'Mohon perjelas rincian konsumsi dan rundown acara.',
        ]);
    }

    public function test_pengurus_can_edit_and_resubmit_after_revision_request()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'revision_requested',
            'notes' => 'Perbaiki anggaran',
        ]);

        // Edit allowed during revision_requested status
        $updateResponse = $this->actingAs($this->pengurusOrgA)->put(route('documents.update', $document), [
            'event_id' => $this->eventOrgA->id,
            'type' => 'proposal',
            'title' => 'Proposal Robotik Setelah Revisi',
            'background' => 'Latar belakang diperbarui.',
            'objectives' => 'Tujuan diperbarui.',
            'budget_plan' => 'Anggaran sudah disesuaikan.',
        ]);
        $updateResponse->assertRedirect(route('documents.show', $document));

        // Re-submit
        $submitResponse = $this->actingAs($this->pengurusOrgA)->post(route('documents.submit', $document));
        $submitResponse->assertRedirect(route('documents.show', $document));

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'title' => 'Proposal Robotik Setelah Revisi',
            'status' => 'submitted',
        ]);
    }

    public function test_pdf_export_is_accessible()
    {
        $document = Document::factory()->create([
            'event_id' => $this->eventOrgA->id,
            'submitted_by' => $this->pengurusOrgA->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->pengurusOrgA)->get(route('documents.pdf', $document));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_document_validation_errors()
    {
        $response = $this->actingAs($this->pengurusOrgA)->post(route('documents.store'), [
            'event_id' => '',
            'type' => 'invalid_type',
            'title' => '',
            'background' => 'short',
            'objectives' => '',
        ]);

        $response->assertSessionHasErrors(['event_id', 'type', 'title', 'background', 'objectives']);
    }
}
