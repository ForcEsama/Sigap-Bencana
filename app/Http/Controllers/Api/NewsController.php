<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsDetailResource;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NewsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['sometimes', 'string', 'in:berita,edukasi'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $query = $this->publishedNewsQuery()
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'image_path',
                'category',
                'source',
                'source_url',
                'status',
                'published_at',
            ]);

        if (isset($filters['category'])) {
            $category = $filters['category'] === 'berita' ? 'Berita' : 'Edukasi';
            $query->where('category', $category);

            if ($category === 'Berita') {
                $query->where('title', 'not like', 'Simulasi Berita:%')
                    ->where('source', 'not like', 'Contoh konten%');
            }
        }

        return NewsResource::collection(
            $query->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate((int) ($filters['per_page'] ?? 10))
        );
    }

    public function show(string $slug): NewsDetailResource
    {
        $article = $this->publishedNewsQuery()
            ->where('slug', $slug)
            ->firstOrFail();

        return new NewsDetailResource($article);
    }

    private function publishedNewsQuery(): Builder
    {
        return News::query()
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query->whereNull('category')->orWhere('category', '<>', 'Peringatan');
            })
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->whereNotNull('source_url')
            ->where('source_url', '<>', '');
    }
}
