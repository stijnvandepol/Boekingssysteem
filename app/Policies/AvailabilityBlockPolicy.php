<?php

namespace App\Policies;

use App\Models\AvailabilityBlock;
use App\Models\User;

class AvailabilityBlockPolicy
{
    public function view(User $user, AvailabilityBlock $block): bool
    {
        return $user->role === 'admin' && $block->resource->user_id === $user->id;
    }

    public function delete(User $user, AvailabilityBlock $block): bool
    {
        return $this->view($user, $block);
    }
}
