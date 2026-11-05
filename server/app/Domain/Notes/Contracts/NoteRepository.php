<?php

namespace App\Domain\Notes\Contracts;

use App\Domain\Notes\NoteData;
use App\Domain\Notes\UpdateNoteData;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NoteRepository
{
    /**
     * @return LengthAwarePaginator<int, Note>
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator;

    public function findForUser(User $user, string $clientUuid): ?Note;

    public function findForUserOrFail(User $user, string $clientUuid): Note;

    public function createIfAbsent(User $user, NoteData $data): Note;

    public function update(Note $note, UpdateNoteData $data): Note;

    public function delete(Note $note): void;
}
