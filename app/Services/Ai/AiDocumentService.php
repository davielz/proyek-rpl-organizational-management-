<?php

namespace App\Services\Ai;

use App\Models\Event;
use App\Services\Ai\Contracts\AiDocumentServiceInterface;
use Illuminate\Support\Facades\Log;

class AiDocumentService implements AiDocumentServiceInterface
{
    public function __construct(
        protected LlmAiService $llmAiService,
        protected FallbackAiService $fallbackAiService
    ) {}

    /**
     * Generate structured draft content for a Proposal or LPJ document.
     */
    public function generateDraft(Event $event, string $type): array
    {
        // 1. If mock mode is explicitly enabled via config/env, return fallback/mock draft immediately
        if (config('ai.mock')) {
            Log::info('[AI Service] Mode Mock AI aktif. Menggunakan data draf tiruan untuk testing.');
            $fallbackData = $this->fallbackAiService->generateFallback($event, $type);

            return [
                'success' => true,
                'is_fallback' => true,
                'message' => 'Draf dokumen berhasil dibuat (Mode Testing Mock).',
                'data' => $fallbackData,
            ];
        }

        // 2. Call LLM API service
        $result = $this->llmAiService->requestDraftGeneration($event, $type);

        // 3. If LLM API succeeded, return the AI generated content
        if ($result['success'] && ! empty($result['data'])) {
            return [
                'success' => true,
                'is_fallback' => false,
                'message' => 'Draf dokumen berhasil dibuat oleh AI Assistant.',
                'data' => $result['data'],
            ];
        }

        // 4. Fallback Strategy: If API fails, timeout, or error, fallback to structured template
        Log::warning('[AI Service] Mengaktifkan Fallback Strategy karena panggilan AI API tidak berhasil', [
            'reason' => $result['error'] ?? 'Unknown error',
            'event_id' => $event->id,
        ]);

        $fallbackData = $this->fallbackAiService->generateFallback($event, $type);

        return [
            'success' => true,
            'is_fallback' => true,
            'message' => 'Layanan AI mengalami kendala ('.$result['error'].'). Draf template otomatis digunakan.',
            'data' => $fallbackData,
        ];
    }
}
