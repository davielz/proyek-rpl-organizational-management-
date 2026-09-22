<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventFeedback>
 */
class EventFeedbackFactory extends Factory
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
            'user_id' => User::factory()->anggota(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentence(8),
            'sentiment' => 'positive',
            'sentiment_score' => 0.90,
        ];
    }
}
