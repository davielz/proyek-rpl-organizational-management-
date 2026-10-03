<?php

namespace Database\Factories;

use App\Models\DiscussionPost;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscussionPost>
 */
class DiscussionPostFactory extends Factory
{
    protected $model = DiscussionPost::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(2, true),
        ];
    }
}
