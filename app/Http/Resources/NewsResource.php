<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsResource extends JsonResource
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
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'image_url' => $this->resolveImageUrl(),
            'category' => $this->category,
            'source' => $this->source,
            'source_url' => $this->source_url,
            'is_simulation' => Str::startsWith($this->title, 'Simulasi Berita:')
                || Str::startsWith($this->source ?? '', 'Contoh konten'),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    private function resolveImageUrl(): ?string
    {
        $imagePath = $this->image_path;
        if (! $imagePath) {
            return null;
        }

        $imageUrl = filter_var($imagePath, FILTER_VALIDATE_URL) !== false
            ? parse_url($imagePath)
            : null;

        if (($imageUrl['scheme'] ?? null) === 'https'
            && ($imageUrl['host'] ?? null) === 'static.bmkg.go.id'
            && preg_match('/\A\/[A-Za-z0-9][A-Za-z0-9._-]*\.jpg\z/i', $imageUrl['path'] ?? '')
            && ! str_contains($imageUrl['path'], '..')) {
            return $imagePath;
        }

        $normalizedPath = str_replace('\\', '/', $imagePath);
        $segments = explode('/', $normalizedPath);
        $isPublicNewsImage = ($segments[0] ?? null) === 'images'
            && ($segments[1] ?? null) === 'news'
            && ! in_array('..', $segments, true)
            && is_file(public_path($normalizedPath));

        return $isPublicNewsImage
            ? asset($normalizedPath)
            : Storage::disk('public')->url($imagePath);
    }
}
