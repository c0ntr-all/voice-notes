<?php

namespace App\Domain\Recordings;

final readonly class RecordingData
{
    public function __construct(
        public string $clientUuid,
        public string $recordedAt,
        public int $durationMs,
        public string $audio,
    ) {}
}
