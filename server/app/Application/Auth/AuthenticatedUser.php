<?php

namespace App\Application\Auth;

use App\Models\User;

final readonly class AuthenticatedUser
{
    public function __construct(
        public User $user,
        public string $token,
    ) {}
}
