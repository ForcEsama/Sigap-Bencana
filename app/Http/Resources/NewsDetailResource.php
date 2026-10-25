<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NewsDetailResource extends NewsResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'content' => $this->content,
        ];
    }
}
