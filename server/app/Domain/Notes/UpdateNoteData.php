<?php

namespace App\Domain\Notes;

final readonly class UpdateNoteData
{
    public function __construct(
        public ?string $body = null,
        public ?string $recordedAt = null,
        public ?int $durationMs = null,
    ) {}
}
