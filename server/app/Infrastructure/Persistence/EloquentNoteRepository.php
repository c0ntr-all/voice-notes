<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Domain\Notes\NoteData;
use App\Domain\Notes\NoteNotFound;
use App\Domain\Notes\UpdateNoteData;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

class EloquentNoteRepository implements NoteRepository
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return Note::query()
            ->whereBelongsTo($user)
            ->latest('recorded_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForUser(User $user, string $clientUuid): ?Note
    {
        return Note::query()
            ->whereBelongsTo($user)
            ->where('client_uuid', $clientUuid)
            ->first();
    }

    public function findForUserOrFail(User $user, string $clientUuid): Note
    {
        return $this->findForUser($user, $clientUuid) ?? throw new NoteNotFound;
    }

    public function createIfAbsent(User $user, NoteData $data): Note
    {
        $existing = $this->findForUser($user, $data->clientUuid);

        if ($existing instanceof Note) {
            return $existing;
        }

        try {
            return $user->notes()->create([
                'client_uuid' => $data->clientUuid,
                'body' => $data->body,
                'recorded_at' => $data->recordedAt,
                'duration_ms' => $data->durationMs,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            return $this->findForUser($user, $data->clientUuid) ?? throw $exception;
        }
    }

    public function update(Note $note, UpdateNoteData $data): Note
    {
        $attributes = array_filter([
            'body' => $data->body,
            'recorded_at' => $data->recordedAt,
            'duration_ms' => $data->durationMs,
        ], static fn (mixed $value): bool => $value !== null);

        if ($attributes === []) {
            return $note;
        }

        $note->fill($attributes)->save();

        return $note;
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }
}
