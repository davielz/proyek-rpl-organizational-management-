<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Ai\Contracts\AiDocumentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiDraftController extends Controller
{
    public function __construct(
        protected AiDocumentServiceInterface $aiService
    ) {}

    /**
     * Generate an AI-assisted draft for Proposal or LPJ.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'type' => ['required', Rule::in(['proposal', 'lpj'])],
        ], [
            'event_id.required' => 'Kegiatan wajib dipilih untuk membuat draf AI.',
            'event_id.exists' => 'Kegiatan yang dipilih tidak ditemukan.',
            'type.required' => 'Jenis dokumen (proposal/lpj) wajib ditentukan.',
            'type.in' => 'Jenis dokumen tidak valid.',
        ]);

        $user = $request->user();
        $event = Event::with('organization')->findOrFail($validated['event_id']);

        // Authorization check: User must be Admin or Pengurus of the event's organization
        if ($user->isPengurus() && ! $user->organizations->pluck('id')->contains($event->organization_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk kegiatan organisasi ini.',
            ], 403);
        }

        if ($user->isAnggota()) {
            return response()->json([
                'success' => false,
                'message' => 'Anggota biasa tidak memiliki akses ke fitur penyusunan dokumen.',
            ], 403);
        }

        $result = $this->aiService->generateDraft($event, $validated['type']);

        return response()->json($result);
    }
}
