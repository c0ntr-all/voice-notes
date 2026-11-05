<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_a_recording_without_creating_a_note(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $uuid = (string) Str::uuid();

        $this->withToken($user->createToken('phone')->plainTextToken)
            ->post('/api/recordings', $this->payload($uuid), ['Idempotency-Key' => $uuid])
            ->assertCreated()
            ->assertJsonPath('data.client_uuid', $uuid)
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.duration_ms', 3200);

        $this->assertSame(1, Recording::query()->count());
        $this->assertSame(0, Note::query()->count());
        Storage::disk('local')->assertExists('recordings/'.$user->id.'/'.$uuid.'.wav');
    }

    public function test_repeating_an_upload_does_not_store_a_second_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;
        $uuid = (string) Str::uuid();

        $this->withToken($token)
            ->post('/api/recordings', $this->payload($uuid), ['Idempotency-Key' => $uuid])
            ->assertCreated();

        $this->withToken($token)
            ->post('/api/recordings', $this->payload($uuid, 4000), ['Idempotency-Key' => $uuid])
            ->assertOk()
            ->assertJsonPath('data.duration_ms', 3200);

        $this->assertSame(1, Recording::query()->count());
        $this->assertSame(1, count(Storage::disk('local')->allFiles()));
    }

    public function test_same_client_uuid_can_belong_to_different_users(): void
    {
        Storage::fake('local');
        $uuid = (string) Str::uuid();

        $this->withToken(User::factory()->create()->createToken('phone')->plainTextToken)
            ->post('/api/recordings', $this->payload($uuid), ['Idempotency-Key' => $uuid])
            ->assertCreated();

        $this->withToken(User::factory()->create()->createToken('phone')->plainTextToken)
            ->post('/api/recordings', $this->payload($uuid), ['Idempotency-Key' => $uuid])
            ->assertCreated();

        $this->assertSame(2, Recording::query()->where('client_uuid', $uuid)->count());
    }

    public function test_upload_requires_auth_and_a_valid_file(): void
    {
        $this->postJson('/api/recordings', [])->assertUnauthorized();

        $user = User::factory()->create();

        $this->withToken($user->createToken('phone')->plainTextToken)
            ->post('/api/recordings', ['client_uuid' => (string) Str::uuid()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['audio', 'recorded_at', 'duration_ms']);
    }

    public function test_idempotency_key_must_match_client_uuid(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->withToken($user->createToken('phone')->plainTextToken)
            ->post('/api/recordings', $this->payload((string) Str::uuid()), [
                'Idempotency-Key' => (string) Str::uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_uuid');

        $this->assertSame(0, Recording::query()->count());
    }

    /**
     * @return array{client_uuid: string, recorded_at: string, duration_ms: int, audio: UploadedFile}
     */
    private function payload(string $uuid, int $durationMs = 3200): array
    {
        return [
            'client_uuid' => $uuid,
            'recorded_at' => '2026-11-05T18:00:00Z',
            'duration_ms' => $durationMs,
            'audio' => $this->wavUpload(),
        ];
    }

    private function wavUpload(): UploadedFile
    {
        $pcm = random_bytes(1600);
        $size = strlen($pcm);
        $header = 'RIFF'.pack('V', 36 + $size).'WAVEfmt '.pack('V', 16)
            .pack('v', 1).pack('v', 1)
            .pack('V', 16000).pack('V', 32000)
            .pack('v', 2).pack('v', 16)
            .'data'.pack('V', $size);

        return UploadedFile::fake()->createWithContent('note.wav', $header.$pcm);
    }
}
