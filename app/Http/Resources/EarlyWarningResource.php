<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarlyWarningResource extends JsonResource
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
            'title' => $this->title,
            'disaster_type' => $this->disaster_type,
            'description' => $this->description,
            'severity' => $this->severity,
            'urgency' => $this->sourceMetadata?->urgency,
            'affected_area' => $this->affected_area,
            'source' => $this->source,
            'source_url' => $this->sourceMetadata?->source_url,
            'published_at' => $this->sourceMetadata?->source_published_at?->toIso8601String(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'status' => $this->status,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
