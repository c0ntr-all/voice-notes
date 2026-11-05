<?php

namespace App\Application\Notes;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Domain\Notes\NoteData;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class UpsertNote
{
    public function __construct(private readonly NoteRepository $notes) {}

    /**
     * Повтор с тем же client_uuid возвращает уже сохранённую заметку и не затирает текст.
     * Офлайн-очередь на телефоне может отправить один POST несколько раз.
     */
    public function handle(User $user, NoteData $data): NoteWriteResult
    {
        Gate::forUser($user)->authorize('create', Note::class);

        $note = $this->notes->createIfAbsent($user, $data);

        return new NoteWriteResult($note, $note->wasRecentlyCreated);
    }
}
