<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_show_update_and_delete_a_note(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $uuid = (string) Str::uuid();

        $created = $this->withToken($token)->postJson('/api/notes', [
            'client_uuid' => $uuid,
            'body' => 'Купить молоко',
            'recorded_at' => '2026-11-05T18:00:00.000Z',
            'duration_ms' => 3200,
        ], ['Idempotency-Key' => $uuid]);

        $created->assertCreated()
            ->assertJsonPath('data.client_uuid', $uuid)
            ->assertJsonPath('data.body', 'Купить молоко')
            ->assertJsonPath('data.duration_ms', 3200)
            ->assertJsonPath('data.source', 'app');

        $this->assertTrue(
            Carbon::parse($created->json('data.recorded_at'))->equalTo('2026-11-05T18:00:00Z'),
        );

        $this->withToken($token)
            ->getJson('/api/notes/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.body', 'Купить молоко');

        $this->withToken($token)
            ->patchJson('/api/notes/'.$uuid, ['body' => 'Купить молоко и хлеб'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Купить молоко и хлеб')
            ->assertJsonPath('data.duration_ms', 3200);

        $this->withToken($token)
            ->putJson('/api/notes/'.$uuid, ['duration_ms' => 4000])
            ->assertOk()
            ->assertJsonPath('data.duration_ms', 4000)
            ->assertJsonPath('data.body', 'Купить молоко и хлеб');

        $this->withToken($token)->deleteJson('/api/notes/'.$uuid)->assertNoContent();

        $this->withToken($token)
            ->getJson('/api/notes/'.$uuid)
            ->assertNotFound()
            ->assertJsonPath('message', 'Заметка не найдена.');

        $this->assertDatabaseMissing('notes', ['client_uuid' => $uuid]);
    }

    public function test_repeating_a_post_does_not_duplicate_or_overwrite_the_note(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $uuid = (string) Str::uuid();

        $this->withToken($token)->postJson('/api/notes', $this->payload($uuid, 'первая версия'), [
            'Idempotency-Key' => $uuid,
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/notes', $this->payload($uuid, 'вторая версия', 2500), [
            'Idempotency-Key' => $uuid,
        ])->assertOk()
            ->assertJsonPath('data.body', 'первая версия')
            ->assertJsonPath('data.duration_ms', 1500);

        $this->assertSame(1, Note::query()->count());
    }

    public function test_idempotency_key_must_match_client_uuid(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->token($user))
            ->postJson('/api/notes', $this->payload((string) Str::uuid(), 'текст'), [
                'Idempotency-Key' => (string) Str::uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_uuid');

        $this->assertSame(0, Note::query()->count());
    }

    public function test_notes_are_listed_newest_first_and_only_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        Note::factory()->for($owner)->create([
            'body' => 'старая',
            'recorded_at' => '2026-11-01 10:00:00',
        ]);
        Note::factory()->for($owner)->create([
            'body' => 'новая',
            'recorded_at' => '2026-11-05 10:00:00',
        ]);
        Note::factory()->for($intruder)->create(['body' => 'чужая']);

        $this->withToken($this->token($owner))
            ->getJson('/api/notes?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'новая')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_user_cannot_read_or_change_another_users_note(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $note = Note::factory()->for($owner)->create(['body' => 'личная']);
        $token = $this->token($intruder);

        $this->withToken($token)->getJson('/api/notes')->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($token)->getJson('/api/notes/'.$note->client_uuid)->assertNotFound();
        $this->withToken($token)->patchJson('/api/notes/'.$note->client_uuid, [
            'body' => 'чужая',
        ])->assertNotFound();
        $this->withToken($token)->deleteJson('/api/notes/'.$note->client_uuid)->assertNotFound();

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'body' => 'личная',
        ]);
    }

    public function test_same_client_uuid_can_belong_to_different_users(): void
    {
        $uuid = (string) Str::uuid();
        $payload = $this->payload($uuid, 'общая мысль');

        $this->withToken($this->token(User::factory()->create()))
            ->postJson('/api/notes', $payload)
            ->assertCreated();

        $this->withToken($this->token(User::factory()->create()))
            ->postJson('/api/notes', $payload)
            ->assertCreated();

        $this->assertSame(2, Note::query()->where('client_uuid', $uuid)->count());
    }

    public function test_store_and_update_validate_the_payload(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $note = Note::factory()->for($user)->create();

        $this->withToken($token)
            ->postJson('/api/notes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client_uuid', 'body', 'recorded_at', 'duration_ms']);

        $this->withToken($token)
            ->patchJson('/api/notes/'.$note->client_uuid, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->withToken($token)
            ->getJson('/api/notes/not-a-uuid')
            ->assertNotFound();
    }

    private function token(User $user): string
    {
        return $user->createToken('phone')->plainTextToken;
    }

    /**
     * @return array{client_uuid: string, body: string, recorded_at: string, duration_ms: int}
     */
    private function payload(string $uuid, string $body, int $durationMs = 1500): array
    {
        return [
            'client_uuid' => $uuid,
            'body' => $body,
            'recorded_at' => '2026-11-05T18:00:00Z',
            'duration_ms' => $durationMs,
        ];
    }
}
