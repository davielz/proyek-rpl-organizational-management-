<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'organization_id' => Organization::factory(),
            'created_by' => User::factory()->pengurus(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->paragraph(),
            'location' => fake()->address(),
            'start_time' => now()->addDays(5),
            'end_time' => now()->addDays(5)->addHours(3),
            'quota' => 50,
            'status' => 'active',
        ];
    }
}
