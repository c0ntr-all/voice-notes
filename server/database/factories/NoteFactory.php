<?php

namespace Database\Factories;

use App\Domain\Notes\NoteSource;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_uuid' => (string) Str::uuid(),
            'body' => fake()->sentence(),
            'recorded_at' => now(),
            'duration_ms' => fake()->numberBetween(500, 60_000),
            'source' => NoteSource::App,
        ];
    }
}
