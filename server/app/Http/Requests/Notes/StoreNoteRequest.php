<?php

namespace App\Http\Requests\Notes;

use App\Domain\Notes\NoteData;
use App\Domain\Notes\NoteSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('client_uuid')) {
            $merge['client_uuid'] = trim((string) $this->input('client_uuid'));
        }

        if ($this->has('body')) {
            $merge['body'] = trim((string) $this->input('body'));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'uuid'],
            'body' => ['required', 'string', 'min:1', 'max:20000'],
            'recorded_at' => ['required', 'date'],
            'duration_ms' => ['required', 'integer', 'min:0', 'max:7200000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_uuid.required' => 'Нужен client_uuid заметки.',
            'client_uuid.uuid' => 'client_uuid должен быть UUID.',
            'body.required' => 'Нужен текст заметки.',
            'body.min' => 'Нужен текст заметки.',
            'body.max' => 'Текст заметки слишком длинный.',
            'recorded_at.required' => 'Нужна дата записи.',
            'recorded_at.date' => 'Дата записи указана неверно.',
            'duration_ms.required' => 'Нужна длительность записи.',
            'duration_ms.integer' => 'Длительность должна быть целым числом миллисекунд.',
            'duration_ms.min' => 'Длительность не может быть отрицательной.',
            'duration_ms.max' => 'Запись длиннее двух часов.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $header = trim((string) $this->header('Idempotency-Key', ''));

            if ($header !== '' && $header !== $this->input('client_uuid')) {
                $validator->errors()->add(
                    'client_uuid',
                    'Заголовок Idempotency-Key должен совпадать с client_uuid.',
                );
            }
        });
    }

    public function toData(): NoteData
    {
        return new NoteData(
            clientUuid: $this->validated('client_uuid'),
            body: $this->validated('body'),
            recordedAt: $this->validated('recorded_at'),
            durationMs: (int) $this->validated('duration_ms'),
            source: NoteSource::App,
        );
    }
}
