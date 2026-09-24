<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        return ! $user->suspended_at && ($comment->user_id === $user->id || $user->can('comments.moderate'));
    }
}
