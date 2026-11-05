<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Recordings\Contracts\RecordingRepository;
use App\Domain\Recordings\RecordingData;
use App\Models\Recording;
use App\Models\User;

class EloquentRecordingRepository implements RecordingRepository
{
    public function findForUser(User $user, string $clientUuid): ?Recording
    {
        return Recording::query()
            ->whereBelongsTo($user)
            ->where('client_uuid', $clientUuid)
            ->first();
    }

    public function create(User $user, RecordingData $data, string $path): Recording
    {
        return $user->recordings()->create([
            'client_uuid' => $data->clientUuid,
            'recorded_at' => $data->recordedAt,
            'duration_ms' => $data->durationMs,
            'path' => $path,
        ]);
    }
}
