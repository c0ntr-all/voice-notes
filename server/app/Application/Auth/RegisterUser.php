<?php

namespace App\Application\Auth;

use App\Domain\Auth\RegisterData;
use App\Domain\Users\Contracts\UserRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class RegisterUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(RegisterData $data): AuthenticatedUser
    {
        try {
            $user = $this->users->create($data->name, $data->email, $data->password);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь с таким email уже зарегистрирован.'],
            ]);
        }

        return new AuthenticatedUser(
            $user,
            $user->createToken($data->deviceName)->plainTextToken,
        );
    }
}
