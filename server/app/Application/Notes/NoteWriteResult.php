<?php

namespace App\Application\Notes;

use App\Models\Note;

final readonly class NoteWriteResult
{
    public function __construct(
        public Note $note,
        public bool $created,
    ) {}
}
