<?php

namespace App\Domain\Auth;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
