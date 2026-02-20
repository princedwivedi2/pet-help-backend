<?php

namespace Database\Factories;

use App\Models\CommunityReply;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityReplyFactory extends Factory
{
    protected $model = CommunityReply::class;

    public function definition(): array
    {
        return [
            'post_id' => CommunityPost::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'parent_id' => null,
        ];
    }

    public function forPost(CommunityPost $post): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $post->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function childOf(CommunityReply $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'post_id' => $parent->post_id,
        ]);
    }
}
