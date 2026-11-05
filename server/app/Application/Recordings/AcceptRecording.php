<?php

namespace App\Application\Recordings;

use App\Domain\Recordings\Contracts\RecordingRepository;
use App\Domain\Recordings\RecordingData;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class AcceptRecording
{
    public function __construct(private readonly RecordingRepository $recordings) {}

    /**
     * Повтор с тем же client_uuid возвращает уже принятую запись и не кладёт второй файл.
     * Распознавание здесь не запускается: текст заметки появится отдельной обработкой.
     */
    public function handle(User $user, RecordingData $data): RecordingWriteResult
    {
        Gate::forUser($user)->authorize('create', Recording::class);

        $existing = $this->recordings->findForUser($user, $data->clientUuid);

        if ($existing instanceof Recording) {
            return new RecordingWriteResult($existing, false);
        }

        $path = 'recordings/'.$user->id.'/'.$data->clientUuid.'.wav';
        Storage::disk('local')->put($path, $data->audio);

        try {
            $recording = $this->recordings->create($user, $data, $path);
        } catch (UniqueConstraintViolationException $exception) {
            $recording = $this->recordings->findForUser($user, $data->clientUuid);

            if (! $recording instanceof Recording) {
                throw $exception;
            }

            return new RecordingWriteResult($recording, false);
        }

        return new RecordingWriteResult($recording, true);
    }
}
