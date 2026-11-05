<?php

namespace App\Application\Notes;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Domain\Notes\UpdateNoteData;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class UpdateNote
{
    public function __construct(private readonly NoteRepository $notes) {}

    public function handle(User $user, string $clientUuid, UpdateNoteData $data): Note
    {
        $note = $this->notes->findForUserOrFail($user, $clientUuid);

        Gate::forUser($user)->authorize('update', $note);

        return $this->notes->update($note, $data);
    }
}
