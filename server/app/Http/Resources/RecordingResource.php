<?php

namespace App\Http\Resources;

use App\Models\Recording;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recording
 */
class RecordingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client_uuid' => $this->client_uuid,
            'status' => 'accepted',
            'recorded_at' => $this->recorded_at->toISOString(),
            'duration_ms' => $this->duration_ms,
        ];
    }
}
