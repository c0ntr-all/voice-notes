<?php

namespace App\Http\Requests\Recordings;

use App\Domain\Recordings\RecordingData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;
use RuntimeException;

class StoreRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('client_uuid')) {
            $this->merge([
                'client_uuid' => trim((string) $this->input('client_uuid')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'uuid'],
            'recorded_at' => ['required', 'date'],
            'duration_ms' => ['required', 'integer', 'min:0', 'max:60000'],
            'audio' => ['required', 'file', 'mimes:wav', 'min:1', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_uuid.required' => 'Нужен client_uuid записи.',
            'client_uuid.uuid' => 'client_uuid должен быть UUID.',
            'recorded_at.required' => 'Нужна дата записи.',
            'recorded_at.date' => 'Дата записи указана неверно.',
            'duration_ms.required' => 'Нужна длительность записи.',
            'duration_ms.integer' => 'Длительность должна быть целым числом миллисекунд.',
            'duration_ms.min' => 'Длительность не может быть отрицательной.',
            'duration_ms.max' => 'Запись длиннее минуты.',
            'audio.required' => 'Нужен файл записи.',
            'audio.file' => 'Нужен файл записи.',
            'audio.mimes' => 'Запись должна быть WAV.',
            'audio.min' => 'Файл записи пустой.',
            'audio.max' => 'Файл записи больше 2 МБ.',
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

    public function toData(): RecordingData
    {
        $file = $this->file('audio');

        if (! $file instanceof UploadedFile) {
            throw new RuntimeException('Нужен файл записи.');
        }

        $audio = file_get_contents($file->getRealPath());

        if ($audio === false || $audio === '') {
            throw new RuntimeException('Не удалось прочитать файл записи.');
        }

        return new RecordingData(
            clientUuid: $this->validated('client_uuid'),
            recordedAt: $this->validated('recorded_at'),
            durationMs: (int) $this->validated('duration_ms'),
            audio: $audio,
        );
    }
}
