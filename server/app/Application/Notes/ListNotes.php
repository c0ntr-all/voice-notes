<?php

namespace App\Application\Notes;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

final class ListNotes
{
    public function __construct(private readonly NoteRepository $notes) {}

    /**
     * @return LengthAwarePaginator<int, Note>
     */
    public function handle(User $user, int $perPage = 50): LengthAwarePaginator
    {
        Gate::forUser($user)->authorize('viewAny', Note::class);

        return $this->notes->paginateForUser($user, max(1, min($perPage, 100)));
    }
}
