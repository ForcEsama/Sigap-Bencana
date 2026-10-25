<?php

namespace App\Services;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BmkgNowcastService
{
    private const FEED_URL = 'https://www.bmkg.go.id/alerts/nowcast/id';

    public function __construct(private readonly ExternalFeedCache $cache) {}

    public function warnings(): array
    {
        $result = $this->cache->remember('bmkg-nowcast-id', 120, fn (): array => $this->load());
        $now = now();
        $result['items'] = array_values(array_filter($result['items'] ?? [], static function (array $item) use ($now): bool {
            try {
                return isset($item['effective'], $item['expires']) && $now->greaterThanOrEqualTo($item['effective']) && $now->lessThan($item['expires']);
            } catch (\Throwable) {
                return false;
            }
        }));

        return $result;
    }

    private function load(): array
    {
        $response = Http::timeout(8)->connectTimeout(4)->withOptions(['allow_redirects' => false])
            ->accept('application/rss+xml, application/xml, text/xml')->get(self::FEED_URL);
        if (! $response->successful()) {
            throw new RuntimeException('BMKG RSS returned HTTP '.$response->status());
        }
        $rss = $this->parseXml($response->body());
        $xpath = new DOMXPath($rss);
        $nodes = $xpath->query('//item');
        if (! $nodes || $nodes->length === 0) {
            throw new RuntimeException('BMKG RSS tidak memiliki item yang dapat dibaca.');
        }
        $capUrls = [];
        $partial = false;
        foreach ($nodes as $node) {
            $url = trim($xpath->evaluate('string(link)', $node));
            if (! $this->isAllowedCapUrl($url)) {
                $partial = true;

                continue;
            }
            $capUrls[] = $url;
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($capUrls) {
                $requests = [];
                foreach ($capUrls as $index => $url) {
                    $requests[] = $pool->as((string) $index)->timeout(8)->connectTimeout(4)
                        ->withOptions(['allow_redirects' => false])->accept('application/xml, text/xml')->get($url);
                }

                return $requests;
            });
        } catch (\Throwable) {
            $responses = [];
        }

        $items = [];
        foreach ($capUrls as $index => $url) {
            $capResponse = $responses[$index] ?? null;
            if (! $capResponse instanceof Response || ! $capResponse->successful()) {
                $partial = true;

                continue;
            }
            try {
                $item = $this->parseCap($this->parseXml($capResponse->body()), $url);
                if ($item !== null) {
                    $items[] = $item;
                }
            } catch (\Throwable) {
                $partial = true;
            }
        }

        return ['status' => $partial ? 'partial' : 'available', 'items' => $items, 'source_url' => self::FEED_URL,
            'message' => $partial ? 'Sebagian detail CAP tidak dapat dibaca.' : null];
    }

    private function parseCap(DOMDocument $document, string $url): ?array
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('cap', 'urn:oasis:names:tc:emergency:cap:1.2');
        $value = static fn (string $path): string => trim($xpath->evaluate('string('.$path.')'));
        if ($value('/cap:alert/cap:status') !== 'Actual' || $value('/cap:alert/cap:scope') !== 'Public') {
            return null;
        }
        if (! in_array($value('/cap:alert/cap:msgType'), ['Alert', 'Update'], true)) {
            return null;
        }
        $effective = $value('/cap:alert/cap:info/cap:effective') ?: $value('/cap:alert/cap:info/cap:onset');
        $expires = $value('/cap:alert/cap:info/cap:expires');
        if ($effective === '' || $expires === '') {
            return null;
        }
        try {
            $effectiveAt = new DateTimeImmutable($effective);
            $expiresAt = new DateTimeImmutable($expires);
        } catch (\Throwable) {
            return null;
        }
        $now = new DateTimeImmutable('now');
        if ($expiresAt <= $now || $effectiveAt > $now) {
            return null;
        }
        $areas = [];
        $areaNodes = $xpath->query('/cap:alert/cap:info/cap:area/cap:areaDesc');
        if ($areaNodes) {
            foreach ($areaNodes as $area) {
                $text = trim($area->textContent);
                if ($text !== '') {
                    $areas[] = $text;
                }
            }
        }
        $event = $value('/cap:alert/cap:info/cap:event');
        $headline = $value('/cap:alert/cap:info/cap:headline') ?: $event;
        if ($event === '' || $headline === '') {
            return null;
        }
        $sent = $value('/cap:alert/cap:sent');
        try {
            $publishedAt = $sent !== '' ? (new DateTimeImmutable($sent))->format(DATE_ATOM) : null;
        } catch (\Throwable) {
            $publishedAt = null;
        }

        return ['kind' => 'official_warning', 'type' => $event, 'region' => $areas ? implode(', ', array_unique($areas)) : null,
            'title' => $headline, 'summary' => $value('/cap:alert/cap:info/cap:description') ?: null,
            'published_at' => $publishedAt, 'effective' => $effectiveAt->format(DATE_ATOM),
            'expires' => $expiresAt->format(DATE_ATOM), 'severity' => $value('/cap:alert/cap:info/cap:severity') ?: null,
            'urgency' => $value('/cap:alert/cap:info/cap:urgency') ?: null, 'certainty' => $value('/cap:alert/cap:info/cap:certainty') ?: null,
            'source' => 'BMKG', 'source_url' => $url];
    }

    private function parseXml(string $xml): DOMDocument
    {
        if ($xml === '' || strlen($xml) > 2_000_000 || preg_match('/<!DOCTYPE/i', $xml)) {
            throw new RuntimeException('XML kosong, terlalu besar, atau mengandung DOCTYPE.');
        }
        $document = new DOMDocument;
        $document->resolveExternals = false;
        $document->substituteEntities = false;
        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
            throw new RuntimeException('Dokumen XML tidak valid.');
        }

        return $document;
    }

    private function isAllowedCapUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts) && ($parts['scheme'] ?? '') === 'https' && strtolower($parts['host'] ?? '') === 'www.bmkg.go.id'
            && str_starts_with($parts['path'] ?? '', '/alerts/nowcast/id/') && str_ends_with($parts['path'] ?? '', '_alert.xml')
            && ! isset($parts['user']) && ! isset($parts['pass']);
    }
}
