<?php

namespace App\Application\Alice;

use App\Domain\Users\Contracts\UserRepository;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;

final class IssueAliceLinkCode
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(User $user): User
    {
        $expiresAt = now()->addMinutes(15);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            try {
                return $this->users->saveLinkCode($user, $code, $expiresAt);
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw new RuntimeException('Не удалось выдать код привязки.');
    }
}
