<?php

namespace App\Application\Auth;

use App\Domain\Auth\LoginData;
use App\Domain\Users\Contracts\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(LoginData $data): AuthenticatedUser
    {
        $user = $this->users->findByEmail($data->email);

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        return new AuthenticatedUser(
            $user,
            $user->createToken($data->deviceName)->plainTextToken,
        );
    }
}
