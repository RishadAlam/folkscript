<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->suspended_at || ! $user->hasVerifiedEmail()) { return false; }
        return $user->hasAnyRole(['editor', 'admin', 'super-admin']) ? true : null;
    }
    public function create(User $user): bool { return $user->canWrite(); }
    public function update(User $user, Post $post): bool { return $user->canWrite() && $post->author_id === $user->id; }
    public function delete(User $user, Post $post): bool { return $this->update($user, $post); }
    public function publish(User $user, Post $post): bool { return $this->update($user, $post) && $user->can('posts.publish'); }
}
