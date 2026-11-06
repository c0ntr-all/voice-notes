<?php

namespace App\Http\Requests\Alice;

use App\Domain\Alice\AliceRequest;
use Illuminate\Foundation\Http\FormRequest;

class AliceWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'string'],
            'session' => ['required', 'array'],
            'session.session_id' => ['required', 'string', 'max:255'],
            'session.message_id' => ['required', 'integer', 'min:0'],
            'session.skill_id' => ['nullable', 'string', 'max:64'],
            'session.user' => ['nullable', 'array'],
            'session.user.user_id' => ['nullable', 'string', 'max:128'],
            'session.user.access_token' => ['nullable', 'string', 'max:2000'],
            'session.application' => ['nullable', 'array'],
            'session.application.application_id' => ['nullable', 'string', 'max:128'],
            'request' => ['required', 'array'],
            'request.command' => ['nullable', 'string', 'max:20000'],
            'request.original_utterance' => ['nullable', 'string', 'max:20000'],
            'request.type' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function toAliceRequest(): AliceRequest
    {
        return AliceRequest::fromPayload($this->validated());
    }
}
