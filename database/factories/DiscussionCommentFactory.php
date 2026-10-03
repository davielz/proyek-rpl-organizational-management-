<?php

namespace Database\Factories;

use App\Models\DiscussionComment;
use App\Models\DiscussionPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscussionComment>
 */
class DiscussionCommentFactory extends Factory
{
    protected $model = DiscussionComment::class;

    public function definition(): array
    {
        return [
            'discussion_post_id' => DiscussionPost::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
        ];
    }
}
