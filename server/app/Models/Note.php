<?php

namespace App\Models;

use App\Domain\Notes\NoteSource;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_uuid', 'body', 'recorded_at', 'duration_ms', 'source'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'duration_ms' => 'integer',
            'source' => NoteSource::class,
        ];
    }
}
