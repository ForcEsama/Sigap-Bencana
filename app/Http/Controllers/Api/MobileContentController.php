<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarlyWarningResource;
use App\Models\EarlyWarning;
use App\Models\EarlyWarningSource;
use App\Models\News;
use App\Services\BmkgNowcastService;
use App\Services\NewsEducationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileContentController extends Controller
{
    public function __construct(
        private readonly NewsEducationService $newsEducation,
        private readonly BmkgNowcastService $nowcast,
    ) {}

    public function news(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category' => ['sometimes', 'string', 'in:berita,edukasi'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);
        $category = $filters['category'] ?? null;
        $items = array_values(array_filter($this->newsEducation->items(), static function (array $item) use ($category): bool {
            return match ($category) {
                'berita' => $item['category_key'] === 'news',
                'edukasi' => in_array($item['category_key'], ['education', 'safety', 'infographic'], true),
                default => true,
            };
        }));
        $items = array_slice($items, 0, (int) ($filters['per_page'] ?? 20));

        return response()->json(['data' => array_map($this->articlePayload(...), $items)]);
    }

    public function newsDetail(string $source, string $item): JsonResponse
    {
        if ($source === 'local') {
            $news = News::query()
                ->where('slug', $item)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->firstOrFail();
            $article = $this->newsEducation->localItem($news);
        } else {
            $article = $this->newsEducation->findExternal($source, $item);
        }

        abort_if($article === null, 404);

        return response()->json(['data' => $this->articlePayload($article) + [
            'content' => $article['content'] ?? $article['summary'] ?? '',
        ]]);
    }

    public function warnings(): JsonResponse
    {
        $databaseWarnings = $this->activeWarningsQuery()
            ->orderByDesc(EarlyWarningSource::query()
                ->select('source_published_at')
                ->whereColumn('early_warning_id', 'early_warnings.id'))
            ->orderByDesc('id')
            ->get()
            ->map(fn (EarlyWarning $warning): array => (new EarlyWarningResource($warning))->toArray(request()))
            ->all();

        $officialWarnings = [];
        foreach ($this->nowcast->warnings()['items'] ?? [] as $warning) {
            $sourceUrl = $warning['source_url'] ?? null;
            $expiresAt = $warning['expires'] ?? null;
            if (! is_string($sourceUrl) || ! is_string($expiresAt) || Carbon::parse($expiresAt)->lessThanOrEqualTo(now())) {
                continue;
            }

            $officialWarnings[] = [
                'id' => (int) hexdec(substr(hash('sha256', $sourceUrl), 0, 7)),
                'title' => $warning['title'],
                'disaster_type' => $warning['type'],
                'description' => $warning['summary'] ?? '',
                'severity' => $warning['severity'] ?: 'Tidak tersedia dari sumber',
                'urgency' => $warning['urgency'],
                'affected_area' => $warning['region'] ?: 'Wilayah tidak dicantumkan',
                'source' => $warning['source'],
                'source_url' => $sourceUrl,
                'published_at' => $warning['published_at'],
                'starts_at' => $warning['effective'],
                'expires_at' => $expiresAt,
                'status' => 'published',
                'updated_at' => null,
            ];
        }

        return response()->json(['data' => [...$databaseWarnings, ...$officialWarnings]]);
    }

    /** @param array<string, mixed> $article
     * @return array<string, mixed>
     */
    private function articlePayload(array $article): array
    {
        return [
            'id' => (string) $article['id'],
            'slug' => (string) $article['id'],
            'source_type' => $article['source_type'],
            'title' => $article['title'],
            'excerpt' => $article['summary'],
            'image_url' => $article['image_url'],
            'category' => $article['category'],
            'source' => $article['source'],
            'source_url' => $article['source_url'],
            'is_simulation' => false,
            'published_at' => $article['published_at'],
        ];
    }

    private function activeWarningsQuery(): Builder
    {
        return EarlyWarning::query()
            ->where('status', 'published')
            ->whereNotNull('source')
            ->where('expires_at', '>', Carbon::now())
            ->whereHas('sourceMetadata', function (Builder $query): void {
                $query->whereNotNull('source_url')->where('source_url', '<>', '');
            })
            ->with('sourceMetadata')
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            });
    }
}
