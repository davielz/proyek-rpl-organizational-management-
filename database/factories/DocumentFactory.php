<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'submitted_by' => User::factory()->pengurus(),
            'approved_by' => null,
            'type' => 'proposal',
            'title' => 'Proposal Kegiatan '.fake()->sentence(3),
            'background' => fake()->paragraphs(2, true),
            'objectives' => fake()->paragraph(),
            'budget_plan' => "1. Konsumsi: Rp 500.000\n2. Perlengkapan: Rp 300.000",
            'summary_content' => fake()->paragraph(),
            'ai_generated_draft' => null,
            'status' => 'draft',
            'notes' => null,
        ];
    }
}
