<?php

namespace App\Policies;

use App\Models\CommunityPost;
use App\Models\User;

class CommunityPostPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CommunityPost $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, CommunityPost $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }
}
