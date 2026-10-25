<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SosRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->resource->reference(),
            'disaster_type' => $this->disaster_type,
            'description' => $this->description,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'address' => $this->address,
            'status' => $this->status,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'events' => $this->whenLoaded('histories', fn () => $this->histories
                ->map(fn ($history): array => [
                    'id' => $history->id,
                    'status' => $history->new_status,
                    'notes' => $history->notes,
                    'occurred_at' => $history->created_at?->toIso8601String(),
                    'actor' => match ($history->actor?->role) {
                        'petugas' => 'Petugas',
                        'admin' => 'Administrator',
                        'masyarakat' => 'Pemohon',
                        default => 'Sistem',
                    },
                ])
                ->values()
                ->all()),
        ];
    }
}
