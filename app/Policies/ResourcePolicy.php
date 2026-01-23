<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    public function view(User $user, Resource $resource): bool
    {
        return $user->role === 'admin' && $resource->user_id === $user->id;
    }

    public function update(User $user, Resource $resource): bool
    {
        return $this->view($user, $resource);
    }
}
