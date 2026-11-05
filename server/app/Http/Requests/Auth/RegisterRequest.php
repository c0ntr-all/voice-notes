<?php

namespace App\Http\Requests\Auth;

use App\Domain\Auth\RegisterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name')) {
            $merge['name'] = trim((string) $this->input('name'));
        }

        if ($this->has('email')) {
            $merge['email'] = Str::lower(trim((string) $this->input('email')));
        }

        if ($this->has('device_name')) {
            $merge['device_name'] = trim((string) $this->input('device_name'));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Нужно имя.',
            'email.required' => 'Нужен email.',
            'email.email' => 'Email указан неверно.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Нужен пароль.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
            'password.min' => 'Пароль должен быть не короче 8 символов.',
        ];
    }

    public function toData(): RegisterData
    {
        $device = $this->validated('device_name');

        return new RegisterData(
            name: $this->validated('name'),
            email: $this->validated('email'),
            password: $this->validated('password'),
            deviceName: is_string($device) && $device !== '' ? $device : 'voice-notes',
        );
    }
}
