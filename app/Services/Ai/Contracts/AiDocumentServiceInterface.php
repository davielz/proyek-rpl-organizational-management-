<?php

namespace App\Services\Ai\Contracts;

use App\Models\Event;

interface AiDocumentServiceInterface
{
    /**
     * Generate structured draft content for a Proposal or LPJ document based on event details.
     *
     * @param  Event  $event  The event model
     * @param  string  $type  'proposal' or 'lpj'
     * @return array{
     *     success: bool,
     *     is_fallback: bool,
     *     message: string,
     *     data: array{
     *         title: string,
     *         background: string,
     *         objectives: string,
     *         budget_plan: string,
     *         summary_content: string
     *     }
     * }
     */
    public function generateDraft(Event $event, string $type): array;
}
