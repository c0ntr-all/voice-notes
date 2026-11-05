<?php

namespace App\Policies;

use App\Models\User;

class RecordingPolicy
{
    public function create(User $user): bool
    {
        return true;
    }
}
