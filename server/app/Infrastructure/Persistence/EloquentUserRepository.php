<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Users\Contracts\UserRepository;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

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

    public function findByAliceId(string $aliceUserId): ?User
    {
        return User::query()->where('alice_user_id', $aliceUserId)->first();
    }

    public function findByActiveLinkCode(string $code): ?User
    {
        return User::query()
            ->where('alice_link_code', $code)
            ->where('alice_link_code_expires_at', '>', now())
            ->first();
    }

    public function findByAccessToken(string $plainTextToken): ?User
    {
        $tokenable = PersonalAccessToken::findToken($plainTextToken)?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }

    public function saveLinkCode(User $user, string $code, DateTimeInterface $expiresAt): User
    {
        $user->forceFill([
            'alice_link_code' => $code,
            'alice_link_code_expires_at' => $expiresAt,
        ])->save();

        return $user;
    }

    public function bindAlice(User $user, string $aliceUserId): void
    {
        $user->forceFill([
            'alice_user_id' => $aliceUserId,
            'alice_link_code' => null,
            'alice_link_code_expires_at' => null,
        ])->save();
    }
}
