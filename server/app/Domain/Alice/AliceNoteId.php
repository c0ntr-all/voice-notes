<?php

namespace App\Domain\Alice;

use Symfony\Component\Uid\Uuid;

final class AliceNoteId
{
    public static function fromSession(string $sessionId, int $messageId): string
    {
        return Uuid::v5(
            Uuid::fromString(Uuid::NAMESPACE_URL),
            'alice:'.$sessionId.':'.$messageId,
        )->toRfc4122();
    }
}
