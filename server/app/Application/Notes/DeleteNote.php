<?php

namespace App\Application\Notes;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteNote
{
    public function __construct(private readonly NoteRepository $notes) {}

    public function handle(User $user, string $clientUuid): void
    {
        $note = $this->notes->findForUserOrFail($user, $clientUuid);

        Gate::forUser($user)->authorize('delete', $note);

        $this->notes->delete($note);
    }
}
