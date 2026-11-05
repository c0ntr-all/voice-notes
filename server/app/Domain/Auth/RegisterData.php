<?php

namespace App\Domain\Auth;

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
