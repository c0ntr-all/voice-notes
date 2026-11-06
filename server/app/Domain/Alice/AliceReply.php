<?php

namespace App\Domain\Alice;

final readonly class AliceReply
{
    public function __construct(
        public string $text,
        public bool $endSession = false,
    ) {}

    /**
     * @return array{response: array{text: string, end_session: bool}, version: string}
     */
    public function toArray(): array
    {
        return [
            'response' => [
                'text' => $this->text,
                'end_session' => $this->endSession,
            ],
            'version' => '1.0',
        ];
    }
}
