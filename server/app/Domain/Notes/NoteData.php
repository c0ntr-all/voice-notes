<?php

namespace App\Domain\Notes;

final readonly class NoteData
{
    public function __construct(
        public string $clientUuid,
        public string $body,
        public string $recordedAt,
        public int $durationMs,
    ) {}
}
