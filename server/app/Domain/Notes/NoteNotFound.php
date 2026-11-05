<?php

namespace App\Domain\Notes;

use RuntimeException;

final class NoteNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Заметка не найдена.');
    }
}
