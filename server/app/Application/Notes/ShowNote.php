<?php

namespace App\Application\Notes;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ShowNote
{
    public function __construct(private readonly NoteRepository $notes) {}

    public function handle(User $user, string $clientUuid): Note
    {
        $note = $this->notes->findForUserOrFail($user, $clientUuid);

        Gate::forUser($user)->authorize('view', $note);

        return $note;
    }
}
