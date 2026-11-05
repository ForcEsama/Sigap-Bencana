<?php

namespace App\Http\Resources;

use App\Models\EvacuationPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacuationPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EvacuationPost $post */
        $post = $this->resource;
        $latitude = is_numeric($post->latitude) ? (float) $post->latitude : null;
        $longitude = is_numeric($post->longitude) ? (float) $post->longitude : null;
        $hasValidCoordinates = $latitude !== null
            && $longitude !== null
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;

        return [
            'id' => $post->id,
            'record_type' => 'evacuation_post',
            'is_planning_candidate' => false,
            'name' => $post->name,
            'description' => $post->description,
            'address' => $post->address,
            'latitude' => $hasValidCoordinates ? $latitude : null,
            'longitude' => $hasValidCoordinates ? $longitude : null,
            'has_valid_coordinates' => $hasValidCoordinates,
            'capacity' => $post->capacity,
            'current_occupancy' => $post->current_occupancy,
            'facilities' => $post->facilities,
            'contact_person' => $post->contact_person,
            'contact_phone' => $post->contact_phone,
            'status' => $post->status,
        ];
    }
}
