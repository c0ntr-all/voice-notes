<?php

namespace App\Domain\Users\Contracts;

use App\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function create(string $name, string $email, string $password): User;
}
