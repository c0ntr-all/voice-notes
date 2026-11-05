<?php

namespace App\Http\Requests\Auth;

use App\Domain\Auth\LoginData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Нужен email.',
            'email.email' => 'Email указан неверно.',
            'password.required' => 'Нужен пароль.',
        ];
    }

    public function toData(): LoginData
    {
        $device = $this->validated('device_name');

        return new LoginData(
            email: $this->validated('email'),
            password: $this->validated('password'),
            deviceName: is_string($device) && $device !== '' ? $device : 'voice-notes',
        );
    }
}
