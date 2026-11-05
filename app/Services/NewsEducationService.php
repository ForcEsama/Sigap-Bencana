<?php

namespace App\Services;

use App\Models\News;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class NewsEducationService
{
    public const CATEGORIES = [
        'all' => 'Semua',
        'news' => 'Berita Terkini',
        'education' => 'Edukasi',
        'safety' => 'Tips Keselamatan',
        'infographic' => 'Infografis',
    ];

    private const BMKG_URL = 'https://www.bmkg.go.id/gempabumi/mitigasi/edukasi-gempabumi-tsunami';

    public function __construct(
        private readonly BnpbNewsService $bnpb,
        private readonly ExternalFeedCache $cache,
    ) {}

    public function items(): array
    {
        $items = [];
        $bnpb = $this->bnpb->news();
        foreach ($bnpb['items'] ?? [] as $article) {
            $imageUrl = $this->safeUrl($article['image_url'] ?? null, ['www.bnpb.go.id']);
            if (empty($article['published_at']) || $imageUrl === null || ! $this->imageIsAvailable($imageUrl, ['www.bnpb.go.id'])) {
                continue;
            }
            $items[] = [
                'id' => hash('sha256', 'bnpb|'.($article['source_url'] ?? '')),
                'source_type' => 'bnpb',
                'category_key' => 'news',
                'category' => self::CATEGORIES['news'],
                'title' => $this->plainText($article['title'] ?? ''),
                'summary' => $this->plainText($article['summary'] ?? ''),
                'published_at' => $article['published_at'],
                'source' => 'BNPB',
                'source_url' => $this->safeUrl($article['source_url'] ?? null, ['www.bnpb.go.id']),
                'image_url' => $imageUrl,
                'image_attribution' => null,
                'feed_status' => $bnpb['status'] ?? 'unavailable',
                'fetched_at' => $bnpb['fetched_at'] ?? null,
            ];
        }

        $education = $this->educationFeed();
        foreach ($education['items'] ?? [] as $entry) {
            $items[] = $entry + [
                'feed_status' => $education['status'] ?? 'unavailable',
                'fetched_at' => $education['fetched_at'] ?? null,
            ];
        }

        foreach ($this->localItems() as $entry) {
            $items[] = $entry;
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? ''));
        });

        return $items;
    }

    public function sourcesUnavailable(): bool
    {
        return ($this->bnpb->news()['status'] ?? 'unavailable') === 'unavailable'
            && ($this->educationFeed()['status'] ?? 'unavailable') === 'unavailable';
    }

    public function hasStaleSource(): bool
    {
        return ($this->bnpb->news()['status'] ?? null) === 'stale'
            || ($this->educationFeed()['status'] ?? null) === 'stale';
    }

    public function staleFetchedAt(): ?string
    {
        $times = [];
        foreach ([$this->bnpb->news(), $this->educationFeed()] as $feed) {
            if (($feed['status'] ?? null) === 'stale' && ! empty($feed['fetched_at'])) {
                $times[] = $feed['fetched_at'];
            }
        }

        return $times === [] ? null : max($times);
    }

    public function findExternal(string $source, string $id): ?array
    {
        if (! in_array($source, ['bnpb', 'bmkg'], true) || ! preg_match('/^[a-f0-9]{64}$/', $id)) {
            return null;
        }

        foreach ($this->items() as $item) {
            if ($item['source_type'] === $source && hash_equals($item['id'], $id)) {
                return $item;
            }
        }

        return null;
    }

    public function localItem(News $news): ?array
    {
        if ($news->status !== 'published' || ! $news->published_at || $news->published_at->isFuture()) {
            return null;
        }
        $categoryKey = $this->localCategory($news->category);
        $path = trim((string) $news->image_path);
        if ($categoryKey === null || ($path !== '' && (str_starts_with($path, '/') || str_contains($path, '..') || ! Storage::disk('public')->exists($path)))) {
            return null;
        }
        $sourceUrl = $this->safeUrl($news->source_url, null);

        return [
            'id' => $news->slug,
            'source_type' => 'local',
            'category_key' => $categoryKey,
            'category' => self::CATEGORIES[$categoryKey],
            'title' => $this->plainText($news->title),
            'summary' => $this->plainText($news->excerpt ?: Str::limit(strip_tags($news->content), 220)),
            'content' => $news->content,
            'published_at' => $news->published_at->toIso8601String(),
            'source' => $this->plainText($news->source ?: 'SIGAP BENCANA'),
            'source_url' => $sourceUrl,
            'image_url' => $path === '' ? null : Storage::disk('public')->url($path),
            'image_attribution' => null,
            'is_simulation' => $this->isSimulation($news),
            'feed_status' => 'available',
            'fetched_at' => null,
        ];
    }

    private function localItems(): array
    {
        if (! Schema::hasTable('news')) {
            return [];
        }

        return News::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(30)
            ->get()
            ->map(fn (News $news): ?array => $this->localItem($news))
            ->filter()
            ->values()
            ->all();
    }

    private function educationFeed(): array
    {
        return $this->cache->remember('bmkg-education', 21600, fn (): array => $this->loadEducation());
    }

    private function loadEducation(): array
    {
        $response = Http::timeout(8)->connectTimeout(4)
            ->withOptions(['allow_redirects' => false])
            ->accept('text/html')->get(self::BMKG_URL);
        if (! $response->successful() || strlen($response->body()) > 5_000_000) {
            throw new \RuntimeException('Materi edukasi BMKG tidak tersedia.');
        }

        $document = $this->parseHtml($response->body());
        $xpath = new DOMXPath($document);
        $images = $xpath->query('//img[@src or @data-src]');
        $items = [];
        foreach ($images ?: [] as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }
            $imageUrl = trim($image->getAttribute('src') ?: $image->getAttribute('data-src'));
            if (! $this->safeUrl($imageUrl, ['i0.wp.com', 'content.bmkg.go.id']) || ! $this->imageIsAvailable($imageUrl, ['i0.wp.com', 'content.bmkg.go.id'])) {
                continue;
            }

            $container = $image;
            for ($depth = 0; $depth < 5 && $container->parentNode instanceof DOMElement; $depth++) {
                $container = $container->parentNode;
                $text = $this->plainText($container->textContent);
                if (mb_strlen($text) > 25) {
                    break;
                }
            }
            $alt = $this->plainText($image->getAttribute('alt'));
            $titleNode = (new DOMXPath($document))->query('.//h2|.//h3|.//h4', $container)?->item(0);
            $title = $this->plainText($titleNode?->textContent ?? $alt);
            if ($title === '' || mb_strlen($title) > 240) {
                continue;
            }

            $paragraph = (new DOMXPath($document))->query('.//p', $container)?->item(0);
            $summary = $this->plainText($paragraph?->textContent ?? '');
            if ($summary === '') {
                continue;
            }
            $titleLower = mb_strtolower($title);
            $categoryKey = (str_contains($titleLower, 'infografis') || str_contains($titleLower, 'poster')) ? 'infographic'
                : (preg_match('/mitigasi|evakuasi|keselamatan|tas siaga|tips/iu', $title) ? 'safety' : 'education');
            $link = $xpath->query('.//a[@href]', $container)?->item(0);
            $target = $link instanceof DOMElement ? $link->getAttribute('href') : self::BMKG_URL;
            $sourceUrl = $this->safeUrl($target, ['www.bmkg.go.id', 'content.bmkg.go.id', 'www.youtube.com', 'youtu.be']) ?: self::BMKG_URL;
            $id = hash('sha256', 'bmkg|'.$title.'|'.$imageUrl);
            $items[$id] = [
                'id' => $id,
                'source_type' => 'bmkg',
                'category_key' => $categoryKey,
                'category' => self::CATEGORIES[$categoryKey],
                'title' => $title,
                'summary' => Str::limit($summary, 260),
                'published_at' => null,
                'source' => 'BMKG',
                'source_url' => $sourceUrl,
                'image_url' => $imageUrl,
                'image_attribution' => 'Materi dan gambar dari BMKG',
            ];
            if (count($items) >= 30) {
                break;
            }
        }

        if ($items === []) {
            throw new \RuntimeException('Tidak ditemukan materi BMKG dengan gambar yang dapat diverifikasi.');
        }

        return ['status' => 'available', 'items' => array_values($items), 'source_url' => self::BMKG_URL];
    }

    private function imageIsAvailable(string $url, array $hosts): bool
    {
        $key = 'sigap:news-image:'.hash('sha256', $url);
        try {
            return Cache::store('file')->remember($key, 1800, function () use ($url, $hosts): bool {
                if ($this->safeUrl($url, $hosts) === null) {
                    return false;
                }
                $response = Http::timeout(5)->connectTimeout(3)
                    ->withOptions(['allow_redirects' => false])
                    ->head($url);

                return $response->successful() && str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/');
            });
        } catch (Throwable) {
            return false;
        }
    }

    private function safeUrl(?string $url, ?array $hosts): ?string
    {
        $url = str_replace(' ', '%20', trim((string) $url));
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if ($hosts !== null && ! in_array(strtolower($parts['host']), $hosts, true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) === false ? null : $url;
    }

    private function localCategory(?string $category): ?string
    {
        return match (Str::lower(trim((string) $category))) {
            'berita', 'berita terkini', 'news' => 'news',
            'edukasi', 'education' => 'education',
            'tips keselamatan', 'tips', 'safety' => 'safety',
            'infografis', 'infographic' => 'infographic',
            default => null,
        };
    }

    private function isSimulation(News $news): bool
    {
        if (array_key_exists('is_simulation', $news->getAttributes())) {
            return (bool) $news->is_simulation;
        }

        return str_starts_with((string) $news->title, 'Simulasi Berita:')
            || str_starts_with((string) $news->source, 'Contoh konten');
    }

    private function plainText(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function parseHtml(string $html): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }
}
