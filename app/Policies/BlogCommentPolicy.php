<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\User;

class BlogCommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, BlogComment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }
}
