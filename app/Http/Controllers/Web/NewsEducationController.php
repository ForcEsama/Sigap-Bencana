<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\NewsEducationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsEducationController extends Controller
{
    public function __construct(private readonly NewsEducationService $content) {}

    public function index(Request $request): View
    {
        $validated = $request->validate(['category' => ['nullable', 'in:all,news,education,safety,infographic']]);
        $category = $validated['category'] ?? 'all';
        $all = $this->content->items();
        $items = array_values(array_filter($all, static fn (array $item): bool => $category === 'all' || $item['category_key'] === $category));

        return view('news-education.index', [
            'items' => $items,
            'category' => $category,
            'categories' => NewsEducationService::CATEGORIES,
            'feedsStale' => $this->content->hasStaleSource(),
            'staleFetchedAt' => $this->content->staleFetchedAt(),
            'feedsUnavailable' => $all === [] && $this->content->sourcesUnavailable(),
        ]);
    }

    public function show(string $source, string $item): View
    {
        if ($source === 'local') {
            $news = News::query()
                ->where('slug', $item)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->firstOrFail();
            $article = $this->content->localItem($news);
            abort_if($article === null, 404);
        } else {
            $article = $this->content->findExternal($source, $item);
            abort_if($article === null, 404);
        }

        return view('news-education.show', ['article' => $article]);
    }
}
