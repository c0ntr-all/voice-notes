<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Users\Contracts\UserRepository;
use App\Models\User;
use Illuminate\Support\Str;

class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', Str::lower($email))->first();
    }

    public function create(string $name, string $email, string $password): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => Str::lower($email),
            'password' => $password,
        ]);
    }
}
