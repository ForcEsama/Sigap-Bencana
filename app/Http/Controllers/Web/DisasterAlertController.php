<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\BmkgFeltEarthquakeService;
use App\Services\BmkgNowcastService;
use App\Services\BnpbWeeklyIncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DisasterAlertController extends Controller
{
    public function __construct(
        private readonly BmkgNowcastService $nowcast,
        private readonly BnpbWeeklyIncidentService $bnpbIncidents,
        private readonly BmkgFeltEarthquakeService $bmkgEarthquakes,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:160'],
            'region' => ['nullable', 'string', 'max:4000'],
        ]);

        $search = trim(data_get($validated, 'q', ''));
        $typeFilter = data_get($validated, 'type', '');
        $regionFilter = data_get($validated, 'region', '');
        $warningFeed = $this->nowcast->warnings();
        $incidentFeed = $this->bnpbIncidents->incidents();
        $earthquakeFeed = $this->bmkgEarthquakes->incidents();
        $cards = [];

        foreach (data_get($warningFeed, 'items', []) as $warning) {
            $sourceUrl = $this->officialWarningUrl(data_get($warning, 'source_url'));
            if ($sourceUrl === null || empty($warning['title']) || empty($warning['expires'])) {
                continue;
            }

            $type = trim((string) (data_get($warning, 'type', ''))) ?: 'Peringatan cuaca';
            $cards[] = [
                'kind' => 'warning',
                'type' => $type,
                'type_label' => $type.' · Peringatan resmi BMKG',
                'title' => trim((string) $warning['title']),
                'summary' => data_get($warning, 'summary'),
                'region' => data_get($warning, 'region'),
                'published_at' => data_get($warning, 'published_at'),
                'time_label' => 'Diterbitkan',
                'source' => 'BMKG',
                'source_url' => $sourceUrl,
                'image_url' => null,
                'image_label' => null,
                'image_fallback_url' => null,
                'image_fallback_label' => null,
                'severity' => data_get($warning, 'severity'),
                'handling_status' => null,
                'feed_status' => data_get($warningFeed, 'status', 'unavailable'),
                'fetched_at' => data_get($warningFeed, 'fetched_at'),
                'details' => array_values(array_filter([
                    ! empty($warning['urgency']) ? 'Urgensi CAP: '.$warning['urgency'] : null,
                    ! empty($warning['certainty']) ? 'Kepastian CAP: '.$warning['certainty'] : null,
                ])),
                'starts_at' => data_get($warning, 'effective'),
                'expires_at' => $warning['expires'],
            ];
        }

        foreach ([$incidentFeed, $earthquakeFeed] as $eventFeed) {
            foreach (data_get($eventFeed, 'items', []) as $incident) {
                $sourceUrl = $this->officialIncidentUrl(data_get($incident, 'source_url'));
                if ($sourceUrl === null || empty($incident['title']) || empty($incident['published_at'])) {
                    continue;
                }

                $type = trim((string) data_get($incident, 'disaster_type', ''));
                $cards[] = [
                    'kind' => 'incident',
                    'type' => $type,
                    'type_label' => $type.' · Kejadian tercatat',
                    'title' => trim((string) $incident['title']),
                    'summary' => trim((string) data_get($incident, 'description', '')) ?: null,
                    'region' => data_get($incident, 'affected_area'),
                    'published_at' => data_get($incident, 'published_at'),
                    'time_label' => 'Tanggal kejadian',
                    'source' => data_get($incident, 'source'),
                    'source_url' => $sourceUrl,
                    'image_url' => null,
                    'image_label' => null,
                    'image_fallback_url' => null,
                    'image_fallback_label' => null,
                    'severity' => null,
                    'handling_status' => null,
                    'feed_status' => data_get($eventFeed, 'status', 'unavailable'),
                    'fetched_at' => data_get($eventFeed, 'fetched_at'),
                    'details' => is_numeric(data_get($incident, 'incident_count'))
                        ? ['Jumlah kejadian dalam catatan BNPB: '.data_get($incident, 'incident_count')]
                        : [],
                    'starts_at' => null,
                    'expires_at' => null,
                ];
            }
        }

        usort($cards, static function (array $left, array $right): int {
            return strtotime((string) (data_get($right, 'published_at', '')))
                <=> strtotime((string) (data_get($left, 'published_at', '')));
        });

        $types = $this->filterOptions($cards, 'type');
        $regions = $this->filterOptions($cards, 'region');
        $cards = array_values(array_filter($cards, static function (array $card) use ($search, $typeFilter, $regionFilter): bool {
            if ($typeFilter !== '' && $card['type'] !== $typeFilter) {
                return false;
            }
            if ($regionFilter !== '' && $card['region'] !== $regionFilter) {
                return false;
            }
            if ($search === '') {
                return true;
            }

            $haystack = implode(' ', array_filter([
                $card['type_label'],
                $card['title'],
                $card['summary'],
                $card['region'],
                $card['source'],
            ], 'is_string'));

            return Str::contains($haystack, $search, ignoreCase: true);
        }));

        return view('disaster-alerts', [
            'cards' => $cards,
            'allSourcesUnavailable' => data_get($warningFeed, 'status') === 'unavailable'
                && data_get($incidentFeed, 'status') === 'unavailable'
                && data_get($earthquakeFeed, 'status') === 'unavailable',
            'incidentFeedStatus' => data_get($incidentFeed, 'status', 'unavailable'),
            'types' => $types,
            'regions' => $regions,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'regionFilter' => $regionFilter,
        ]);
    }

    public function showNews(string $slug): View
    {
        $article = News::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('disaster-news-show', [
            'article' => [
                'title' => $article->title,
                'category' => $article->category,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'source' => $article->source,
                'source_url' => $this->safeHttpUrl($article->source_url),
                'published_at' => $article->published_at,
                'image_url' => $this->publicImageUrl($article->image_path),
            ],
        ]);
    }

    private function officialWarningUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $parts = parse_url($url);

        if (! is_array($parts)
            || (data_get($parts, 'scheme')) !== 'https'
            || strtolower((string) (data_get($parts, 'host', ''))) !== 'www.bmkg.go.id'
            || ! str_starts_with((string) (data_get($parts, 'path', '')), '/alerts/nowcast/id/')) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) === false ? null : $url;
    }

    private function officialIncidentUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $parts = parse_url($url);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $isBnpbLayer = $host === 'gis.bnpb.go.id'
            && $path === '/server/rest/services/Kejadian_Bencana_Mingguan/MapServer/25';
        $isBmkgEarthquakePage = $host === 'data.bmkg.go.id' && $path === '/gempabumi/';
        if (! $isBnpbLayer && ! $isBmkgEarthquakePage) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) === false ? null : $url;
    }

    private function safeHttpUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    private function publicImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..')) {
            return null;
        }

        $disk = Storage::disk('public');

        return $disk->exists($path) ? $disk->url($path) : null;
    }

    private function filterOptions(array $cards, string $key): array
    {
        $options = [];
        foreach ($cards as $card) {
            $value = trim((string) (data_get($card, $key, '')));
            if ($value !== '') {
                $options[$value] = $value;
            }
        }
        natcasesort($options);

        return array_values($options);
    }
}
