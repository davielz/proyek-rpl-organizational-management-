<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => 'UKM '.$name,
            'code' => strtoupper(fake()->unique()->lexify('UKM-???')),
            'description' => fake()->paragraph(),
            'logo' => null,
        ];
    }
}
