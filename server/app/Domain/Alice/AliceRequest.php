<?php

namespace App\Domain\Alice;

final readonly class AliceRequest
{
    public function __construct(
        public string $sessionId,
        public int $messageId,
        public string $command,
        public string $originalUtterance,
        public ?string $yandexUserId,
        public ?string $applicationId,
        public ?string $accessToken,
        public ?string $skillId,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $session = is_array($payload['session'] ?? null) ? $payload['session'] : [];
        $request = is_array($payload['request'] ?? null) ? $payload['request'] : [];
        $user = is_array($session['user'] ?? null) ? $session['user'] : [];
        $application = is_array($session['application'] ?? null) ? $session['application'] : [];

        return new self(
            sessionId: (string) ($session['session_id'] ?? ''),
            messageId: (int) ($session['message_id'] ?? 0),
            command: trim((string) ($request['command'] ?? '')),
            originalUtterance: trim((string) ($request['original_utterance'] ?? '')),
            yandexUserId: self::nullableString($user['user_id'] ?? null),
            applicationId: self::nullableString($application['application_id'] ?? null),
            accessToken: self::nullableString($user['access_token'] ?? null),
            skillId: self::nullableString($session['skill_id'] ?? null),
        );
    }

    public function noteBody(): string
    {
        if ($this->originalUtterance !== '') {
            return $this->originalUtterance;
        }

        return $this->command;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
