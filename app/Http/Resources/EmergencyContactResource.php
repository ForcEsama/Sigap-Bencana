<?php

namespace App\Http\Resources;

use App\Models\EmergencyContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EmergencyContact $contact */
        $contact = $this->resource;

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'category' => $contact->category,
            'phone' => $contact->phone,
            'description' => $contact->description,
            'region' => $contact->region,
            'address' => $contact->address,
            'website_url' => $contact->website_url,
        ];
    }
}
