<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\ReviewDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $user = $request->user();
        $query = Document::with(['event.organization', 'submitter', 'approver']);

        if ($user->isPengurus()) {
            $orgIds = $user->organizations->pluck('id');
            $query->whereHas('event', function ($q) use ($orgIds) {
                $q->whereIn('organization_id', $orgIds);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('event', function ($eq) use ($search) {
                        $eq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        $documents = $query->latest()->paginate(10)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Document::class);

        $user = $request->user();
        $eventsQuery = Event::query();

        if ($user->isPengurus()) {
            $orgIds = $user->organizations->pluck('id');
            $eventsQuery->whereIn('organization_id', $orgIds);
        }

        $events = $eventsQuery->orderBy('title')->get();
        $selectedEventId = $request->get('event_id');
        $selectedType = $request->get('type', 'proposal');

        return view('documents.create', compact('events', 'selectedEventId', 'selectedType'));
    }

    public function store(StoreDocumentRequest $request)
    {
        $user = $request->user();
        $event = Event::findOrFail($request->event_id);

        if ($user->isPengurus() && ! $user->organizations->pluck('id')->contains($event->organization_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat dokumen kegiatan organisasi ini.');
        }

        $document = Document::create([
            'event_id' => $event->id,
            'submitted_by' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'background' => $request->background,
            'objectives' => $request->objectives,
            'budget_plan' => $request->budget_plan,
            'summary_content' => $request->summary_content,
            'status' => 'draft',
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Draft dokumen '.strtoupper($document->type).' berhasil dibuat.');
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['event.organization', 'submitter', 'approver']);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        $user = auth()->user();
        $eventsQuery = Event::query();

        if ($user->isPengurus()) {
            $orgIds = $user->organizations->pluck('id');
            $eventsQuery->whereIn('organization_id', $orgIds);
        }

        $events = $eventsQuery->orderBy('title')->get();

        return view('documents.edit', compact('document', 'events'));
    }

    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $this->authorize('update', $document);

        $event = Event::findOrFail($request->event_id);

        if ($request->user()->isPengurus() && ! $request->user()->organizations->pluck('id')->contains($event->organization_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk memindahkan dokumen ke kegiatan organisasi lain.');
        }

        $document->update([
            'event_id' => $event->id,
            'type' => $request->type,
            'title' => $request->title,
            'background' => $request->background,
            'objectives' => $request->objectives,
            'budget_plan' => $request->budget_plan,
            'summary_content' => $request->summary_content,
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Perubahan dokumen berhasil disimpan.');
    }

    public function submit(Request $request, Document $document)
    {
        $this->authorize('submit', $document);

        $document->update([
            'status' => 'submitted',
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Dokumen '.strtoupper($document->type).' berhasil diajukan untuk direview Admin.');
    }

    public function review(ReviewDocumentRequest $request, Document $document)
    {
        $this->authorize('review', $document);

        if ($request->action === 'approve') {
            $document->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'notes' => $request->notes,
            ]);

            $message = 'Dokumen berhasil disetujui.';
        } elseif ($request->action === 'request_revision') {
            $document->update([
                'status' => 'revision_requested',
                'notes' => $request->notes,
            ]);

            $message = 'Permintaan revisi telah dikirimkan ke pengurus.';
        } else {
            $document->update([
                'status' => 'rejected',
                'notes' => $request->notes,
            ]);

            $message = 'Dokumen ditolak.';
        }

        return redirect()->route('documents.show', $document)
            ->with('success', $message);
    }

    public function exportPdf(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['event.organization', 'submitter', 'approver']);

        $pdf = Pdf::loadView('documents.pdf', compact('document'))
            ->setPaper('a4', 'portrait');

        $filename = strtoupper($document->type).'_'.$document->event->slug.'.pdf';

        return $pdf->download($filename);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        $type = strtoupper($document->type);
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', "Dokumen {$type} berhasil dihapus.");
    }
}
