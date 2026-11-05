<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarlyWarningResource;
use App\Models\EarlyWarning;
use App\Models\EarlyWarningSource;
use App\Models\News;
use App\Services\BmkgFeltEarthquakeService;
use App\Services\BmkgNowcastService;
use App\Services\BnpbWeeklyIncidentService;
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
        private readonly BnpbWeeklyIncidentService $bnpbIncidents,
        private readonly BmkgFeltEarthquakeService $bmkgEarthquakes,
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

        foreach ($databaseWarnings as &$warning) {
            $warning['kind'] = 'warning';
        }
        unset($warning);

        $warningFeed = $this->nowcast->warnings();
        $officialWarnings = [];
        foreach ($warningFeed['items'] ?? [] as $warning) {
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
                'kind' => 'warning',
            ];
        }

        $incidentFeeds = [
            'bnpb_incidents' => $this->bnpbIncidents->incidents(),
            'bmkg_earthquakes' => $this->bmkgEarthquakes->incidents(),
        ];
        $incidents = [];
        foreach ($incidentFeeds as $feed) {
            foreach ($feed['items'] ?? [] as $incident) {
                $incidents[] = [
                    'id' => (int) $incident['id'],
                    'title' => $incident['title'],
                    'disaster_type' => $incident['disaster_type'],
                    'description' => $incident['description'],
                    'severity' => 'Tidak ditetapkan oleh sumber kejadian',
                    'urgency' => null,
                    'affected_area' => $incident['affected_area'],
                    'source' => $incident['source'],
                    'source_url' => $incident['source_url'],
                    'published_at' => $incident['published_at'],
                    'starts_at' => null,
                    'expires_at' => null,
                    'status' => 'reported',
                    'updated_at' => $feed['fetched_at'] ?? null,
                    'kind' => 'incident',
                    'incident_count' => $incident['incident_count'],
                ];
            }
        }

        return response()->json([
            'data' => [...$databaseWarnings, ...$officialWarnings, ...$incidents],
            'meta' => [
                'sources' => [
                    'bmkg_weather_warnings' => [
                        'status' => $warningFeed['status'] ?? 'unavailable',
                        'fetched_at' => $warningFeed['fetched_at'] ?? null,
                    ],
                    'bnpb_incidents' => $this->feedMetadata($incidentFeeds['bnpb_incidents']),
                    'bmkg_earthquakes' => $this->feedMetadata($incidentFeeds['bmkg_earthquakes']),
                ],
            ],
        ]);
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
            'is_simulation' => (bool) ($article['is_simulation'] ?? false),
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

    /** @param array<string, mixed> $feed
     * @return array{status: string, fetched_at: ?string, records_returned: ?int, records_mapped: ?int}
     */
    private function feedMetadata(array $feed): array
    {
        return [
            'status' => (string) ($feed['status'] ?? 'unavailable'),
            'fetched_at' => $feed['fetched_at'] ?? null,
            'records_returned' => isset($feed['records_returned']) ? (int) $feed['records_returned'] : null,
            'records_mapped' => isset($feed['records_mapped']) ? (int) $feed['records_mapped'] : null,
        ];
    }
}
