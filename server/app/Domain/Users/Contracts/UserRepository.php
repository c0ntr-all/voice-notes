<?php

namespace App\Domain\Users\Contracts;

use App\Models\User;
use DateTimeInterface;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function create(string $name, string $email, string $password): User;

    public function findByAliceId(string $aliceUserId): ?User;

    public function findByActiveLinkCode(string $code): ?User;

    public function findByAccessToken(string $plainTextToken): ?User;

    public function saveLinkCode(User $user, string $code, DateTimeInterface $expiresAt): User;

    public function bindAlice(User $user, string $aliceUserId): void;
}
