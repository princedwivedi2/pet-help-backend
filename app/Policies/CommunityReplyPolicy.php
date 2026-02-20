<?php

namespace App\Policies;

use App\Models\CommunityReply;
use App\Models\User;

class CommunityReplyPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, CommunityReply $reply): bool
    {
        return $user->id === $reply->user_id || $user->isAdmin();
    }
}
