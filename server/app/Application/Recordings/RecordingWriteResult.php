<?php

namespace App\Application\Recordings;

use App\Models\Recording;

final readonly class RecordingWriteResult
{
    public function __construct(
        public Recording $recording,
        public bool $created,
    ) {}
}
