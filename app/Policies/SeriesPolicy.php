<?php

namespace App\Policies;

use App\Models\Series;
use App\Models\User;

class SeriesPolicy
{
    public function create(User $user): bool { return $user->canWrite(); }

    public function update(User $user, Series $series): bool
    {
        return $user->canWrite() && ($user->id === $series->author_id || $user->hasAnyRole(['editor', 'admin', 'super-admin']) || $user->can('posts.edit-any'));
    }

    public function delete(User $user, Series $series): bool { return $this->update($user, $series); }
}
