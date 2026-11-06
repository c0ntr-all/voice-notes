<?php

namespace App\Http\Resources;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Note
 */
class NoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client_uuid' => $this->client_uuid,
            'body' => $this->body,
            'recorded_at' => $this->recorded_at->toISOString(),
            'duration_ms' => $this->duration_ms,
            'source' => $this->source->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
