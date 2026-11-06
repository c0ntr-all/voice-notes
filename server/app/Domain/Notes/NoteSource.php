<?php

namespace App\Domain\Notes;

enum NoteSource: string
{
    case App = 'app';
    case Alice = 'alice';
}
