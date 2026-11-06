<?php

namespace Tests\Feature;

use App\Domain\Notes\NoteSource;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AliceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_answers_and_does_not_create_a_note(): void
    {
        $this->postJson('/api/alice', $this->alicePayload([
            'request' => [
                'command' => 'ping',
                'original_utterance' => 'ping',
            ],
        ]))->assertOk()
            ->assertJsonPath('response.text', 'pong')
            ->assertJsonPath('response.end_session', false)
            ->assertJsonPath('version', '1.0');

        $this->assertSame(0, Note::query()->count());
    }

    public function test_unlinked_alice_user_does_not_create_a_note(): void
    {
        $this->postJson('/api/alice', $this->alicePayload())
            ->assertOk()
            ->assertJsonPath('response.text', 'Сначала назовите код привязки из приложения.');

        $this->assertSame(0, Note::query()->count());
    }

    public function test_link_code_then_utterance_creates_an_alice_note(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;

        $code = $this->withToken($token)
            ->postJson('/api/alice/link-code')
            ->assertOk()
            ->json('code');

        $this->postJson('/api/alice', $this->alicePayload([
            'request' => [
                'command' => (string) $code,
                'original_utterance' => (string) $code,
            ],
        ]))->assertOk()
            ->assertJsonPath('response.text', 'Аккаунт привязан. Теперь скажите текст заметки.');

        $this->postJson('/api/alice', $this->alicePayload([
            'session' => ['message_id' => 2],
        ]))->assertOk()
            ->assertJsonPath('response.text', 'Записала: Купить молоко');

        $note = Note::query()->firstOrFail();
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame(NoteSource::Alice, $note->source);
        $this->assertSame('Купить молоко', $note->body);
        $this->assertSame(0, $note->duration_ms);
        $this->assertSame('yandex-user-1', $user->fresh()->alice_user_id);
    }

    public function test_the_same_alice_message_does_not_create_a_duplicate(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['alice_user_id' => 'yandex-user-1'])->save();

        $payload = $this->alicePayload();

        $this->postJson('/api/alice', $payload)->assertOk()->assertJsonPath('response.text', 'Записала: Купить молоко');
        $this->postJson('/api/alice', $payload)->assertOk()->assertJsonPath('response.text', 'Эта фраза уже записана.');

        $this->assertSame(1, Note::query()->count());
    }

    public function test_access_token_creates_a_note_without_a_link_code(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('alice')->plainTextToken;

        $this->postJson('/api/alice', $this->alicePayload([
            'session' => [
                'user' => [
                    'user_id' => 'someone-else',
                    'access_token' => $token,
                ],
            ],
        ]))->assertOk()->assertJsonPath('response.text', 'Записала: Купить молоко');

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'source' => NoteSource::Alice->value,
            'body' => 'Купить молоко',
        ]);
    }

    public function test_expired_link_code_and_foreign_skill_do_not_save_a_note(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'alice_link_code' => '111111',
            'alice_link_code_expires_at' => now()->subMinute(),
        ])->save();

        $this->postJson('/api/alice', $this->alicePayload([
            'request' => [
                'command' => '111111',
                'original_utterance' => '111111',
            ],
        ]))->assertOk()
            ->assertJsonPath('response.text', 'Код не найден или его срок истёк. Запросите новый в приложении.');

        config(['services.alice.skill_id' => 'expected-skill']);

        $this->postJson('/api/alice', $this->alicePayload([
            'session' => ['skill_id' => 'other-skill', 'message_id' => 3],
        ]))->assertOk()
            ->assertJsonPath('response.text', 'Навык не настроен для этого сервера.');

        $this->assertSame(0, Note::query()->count());
    }

    public function test_alice_account_cannot_be_rebound_to_another_user(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['alice_user_id' => 'yandex-user-1'])->save();

        $other = User::factory()->create();
        $other->forceFill([
            'alice_link_code' => '222222',
            'alice_link_code_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->postJson('/api/alice', $this->alicePayload([
            'request' => [
                'command' => '222222',
                'original_utterance' => '222222',
            ],
        ]))->assertOk()
            ->assertJsonPath('response.text', 'Этот аккаунт Алисы уже привязан к другому пользователю.');

        $this->assertNull($other->fresh()->alice_user_id);
    }

    public function test_link_code_requires_auth_and_a_broken_webhook_is_rejected(): void
    {
        $this->postJson('/api/alice/link-code')->assertUnauthorized();
        $this->postJson('/api/alice', [])->assertUnprocessable();
    }

    public function test_the_app_cannot_mark_its_note_as_created_by_alice(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('phone')->plainTextToken)
            ->postJson('/api/notes', [
                'client_uuid' => (string) Str::uuid(),
                'body' => 'Из приложения',
                'recorded_at' => '2026-11-06T12:00:00Z',
                'duration_ms' => 1000,
                'source' => 'alice',
            ])->assertCreated()
            ->assertJsonPath('data.source', 'app');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function alicePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'version' => '1.0',
            'session' => [
                'message_id' => 1,
                'session_id' => 'session-1',
                'skill_id' => 'skill-1',
                'user' => [
                    'user_id' => 'yandex-user-1',
                ],
                'application' => [
                    'application_id' => 'device-1',
                ],
                'new' => false,
            ],
            'request' => [
                'command' => 'купить молоко',
                'original_utterance' => 'Купить молоко',
                'type' => 'SimpleUtterance',
            ],
        ], $overrides);
    }
}
