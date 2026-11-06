<?php

namespace App\Application\Alice;

use App\Application\Notes\UpsertNote;
use App\Domain\Alice\AliceNoteId;
use App\Domain\Alice\AliceReply;
use App\Domain\Alice\AliceRequest;
use App\Domain\Notes\NoteData;
use App\Domain\Notes\NoteSource;
use App\Domain\Users\Contracts\UserRepository;
use App\Models\User;

final class HandleAliceRequest
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UpsertNote $upsertNote,
    ) {}

    public function handle(AliceRequest $request): AliceReply
    {
        $command = mb_strtolower($request->command);

        if ($command === 'ping') {
            return new AliceReply('pong');
        }

        if ($this->skillIsRejected($request)) {
            return new AliceReply('Навык не настроен для этого сервера.');
        }

        if (in_array($command, ['выход', 'хватит', 'стоп', 'отмена'], true)) {
            return new AliceReply('Хорошо.', endSession: true);
        }

        if (in_array($command, ['помощь', 'что ты умеешь'], true)) {
            return new AliceReply('Скажите текст заметки. Чтобы привязать аккаунт, назовите код из приложения.');
        }

        $code = $this->linkCode($command);

        if ($code !== null) {
            return $this->link($request, $code);
        }

        $user = $this->resolveUser($request);

        if ($user === null) {
            return new AliceReply('Сначала назовите код привязки из приложения.');
        }

        $body = $request->noteBody();

        if ($body === '') {
            return new AliceReply('Что записать?');
        }

        if (mb_strlen($body) > 20000) {
            return new AliceReply('Слишком длинная заметка.');
        }

        $result = $this->upsertNote->handle($user, new NoteData(
            clientUuid: AliceNoteId::fromSession($request->sessionId, $request->messageId),
            body: $body,
            recordedAt: now()->toISOString(),
            durationMs: 0,
            source: NoteSource::Alice,
        ));

        if (! $result->created) {
            return new AliceReply('Эта фраза уже записана.');
        }

        return new AliceReply($this->spoken($body));
    }

    private function skillIsRejected(AliceRequest $request): bool
    {
        $expected = config('services.alice.skill_id');

        if (! is_string($expected) || $expected === '') {
            return false;
        }

        return $request->skillId !== $expected;
    }

    private function link(AliceRequest $request, string $code): AliceReply
    {
        $aliceId = $request->yandexUserId ?? $request->applicationId;

        if ($aliceId === null) {
            return new AliceReply('Не вижу идентификатор пользователя Алисы.');
        }

        $owner = $this->users->findByActiveLinkCode($code);

        if ($owner === null) {
            return new AliceReply('Код не найден или его срок истёк. Запросите новый в приложении.');
        }

        $taken = $this->users->findByAliceId($aliceId);

        if ($taken !== null && $taken->id !== $owner->id) {
            return new AliceReply('Этот аккаунт Алисы уже привязан к другому пользователю.');
        }

        $this->users->bindAlice($owner, $aliceId);

        return new AliceReply('Аккаунт привязан. Теперь скажите текст заметки.');
    }

    private function resolveUser(AliceRequest $request): ?User
    {
        if ($request->accessToken !== null) {
            $user = $this->users->findByAccessToken($request->accessToken);

            if ($user !== null) {
                return $user;
            }
        }

        foreach (array_filter([$request->yandexUserId, $request->applicationId]) as $aliceId) {
            $user = $this->users->findByAliceId($aliceId);

            if ($user !== null) {
                return $user;
            }
        }

        return null;
    }

    private function linkCode(string $command): ?string
    {
        $compact = preg_replace('/\s+/', '', $command) ?? '';

        if (preg_match('/^\d{6}$/', $compact) === 1) {
            return $compact;
        }

        if (preg_match('/^код(\d{6})$/u', $compact, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function spoken(string $body): string
    {
        $prefix = 'Записала: ';
        $available = 1000 - mb_strlen($prefix);

        if (mb_strlen($body) <= $available) {
            return $prefix.$body;
        }

        return $prefix.mb_substr($body, 0, $available - 1).'…';
    }
}
