<?php

namespace App\Http\Requests\Notes;

use App\Domain\Notes\UpdateNoteData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('body')) {
            $this->merge([
                'body' => trim((string) $this->input('body')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'required', 'string', 'min:1', 'max:20000'],
            'recorded_at' => ['sometimes', 'required', 'date'],
            'duration_ms' => ['sometimes', 'required', 'integer', 'min:0', 'max:7200000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Нужен текст заметки.',
            'body.min' => 'Нужен текст заметки.',
            'body.max' => 'Текст заметки слишком длинный.',
            'recorded_at.date' => 'Дата записи указана неверно.',
            'duration_ms.integer' => 'Длительность должна быть целым числом миллисекунд.',
            'duration_ms.min' => 'Длительность не может быть отрицательной.',
            'duration_ms.max' => 'Запись длиннее двух часов.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->exists('body') && ! $this->exists('recorded_at') && ! $this->exists('duration_ms')) {
                $validator->errors()->add(
                    'body',
                    'Нужно передать хотя бы одно поле: body, recorded_at или duration_ms.',
                );
            }
        });
    }

    public function toData(): UpdateNoteData
    {
        $validated = $this->validated();

        return new UpdateNoteData(
            body: $validated['body'] ?? null,
            recordedAt: $validated['recorded_at'] ?? null,
            durationMs: array_key_exists('duration_ms', $validated) ? (int) $validated['duration_ms'] : null,
        );
    }
}
