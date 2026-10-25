<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BnpbNewsService
{
    private const INDEX_URL = 'https://www.bnpb.go.id/berita';

    public function __construct(private readonly ExternalFeedCache $cache) {}

    public function news(): array
    {
        return $this->cache->remember('bnpb-news', 1800, fn (): array => $this->load());
    }

    private function load(): array
    {
        $response = Http::timeout(8)->connectTimeout(4)->withOptions(['allow_redirects' => false])->accept('text/html')->get(self::INDEX_URL);
        if (! $response->successful() || strlen($response->body()) > 5_000_000) {
            throw new RuntimeException('Halaman berita BNPB tidak tersedia atau terlalu besar.');
        }
        $xpath = new DOMXPath($this->parseHtml($response->body()));
        $nodes = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " tracking ") and @data-type="news"]');
        if (! $nodes || $nodes->length === 0) {
            throw new RuntimeException('Struktur daftar berita BNPB berubah.');
        }
        $cards = [];
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $url = trim($node->getAttribute('href'));
            if (! $this->isBnpbUrl($url) || isset($cards[$url])) {
                continue;
            }
            $card = $this->findCard($node);
            if ($card !== null) {
                $cards[$url] = $card;
            }
            if (count($cards) >= 6) {
                break;
            }
        }
        if (! $cards) {
            throw new RuntimeException('Tidak ditemukan kartu berita BNPB yang valid.');
        }
        $urls = array_keys($cards);
        try {
            $details = Http::pool(function (Pool $pool) use ($urls) {
                $requests = [];
                foreach ($urls as $index => $url) {
                    $requests[] = $pool->as((string) $index)->timeout(5)->connectTimeout(3)->withOptions(['allow_redirects' => false])->accept('text/html')->get($url);
                }

                return $requests;
            });
        } catch (\Throwable) {
            $details = [];
        }
        $items = [];
        foreach ($urls as $index => $url) {
            $metadata = [];
            $detail = $details[$index] ?? null;
            if ($detail && $detail->successful() && strlen($detail->body()) <= 5_000_000) {
                try {
                    $metadata = $this->parseArticle($detail->body());
                } catch (\Throwable) { /* keep listing fields */
                }
            }
            $title = $metadata['title'] ?? $cards[$url]['title'];
            if ($title === '') {
                continue;
            }
            $imageUrl = $cards[$url]['image_url'];
            $items[] = ['kind' => 'bnpb_news', 'type' => null, 'region' => null, 'title' => $title,
                'summary' => $metadata['summary'] ?? null, 'published_at' => $cards[$url]['published_at'],
                'image_url' => $this->isBnpbUrl($imageUrl ?? '') ? $imageUrl : null, 'source' => 'BNPB', 'source_url' => $url];
        }
        if (! $items) {
            throw new RuntimeException('Respons BNPB tidak memiliki berita yang valid.');
        }

        return ['status' => 'available', 'items' => $items, 'source_url' => self::INDEX_URL];
    }

    private function findCard(DOMElement $link): ?array
    {
        $card = $link;
        while ($card->parentNode instanceof DOMElement && ! str_contains($card->getAttribute('class'), 'col-md-4')) {
            $card = $card->parentNode;
        }
        $xpath = new DOMXPath($card->ownerDocument);
        $image = $xpath->query('.//img', $card)?->item(0);
        $date = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " date ")]', $card)?->item(0);
        $title = $image instanceof DOMElement ? trim($image->getAttribute('alt')) : trim($link->textContent);
        $publishedAt = $date ? $this->parseIndonesianDate($date->textContent) : null;
        if ($publishedAt !== null && now()->lt($publishedAt)) {
            return null;
        }

        return $title !== '' ? ['title' => $title, 'published_at' => $publishedAt,
            'image_url' => $image instanceof DOMElement ? trim($image->getAttribute('src')) : null] : null;
    }

    private function parseArticle(string $html): array
    {
        $xpath = new DOMXPath($this->parseHtml($html));
        $metadata = [];
        foreach (['og:title' => 'title', 'og:description' => 'summary'] as $property => $key) {
            $node = $xpath->query('//meta[@property="'.$property.'"]/@content')->item(0);
            if ($node) {
                $value = html_entity_decode(trim($node->nodeValue), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
                if ($value !== '') {
                    $metadata[$key] = $value;
                }
            }
        }

        return $metadata;
    }

    private function parseHtml(string $html): DOMDocument
    {
        if ($html === '' || strlen($html) > 5_000_000) {
            throw new RuntimeException('HTML kosong atau terlalu besar.');
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded) {
            throw new RuntimeException('HTML sumber tidak dapat diproses.');
        }

        return $document;
    }

    private function parseIndonesianDate(string $text): ?string
    {
        if (! preg_match('/\b(\d{1,2})\s+(Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des)\s+(\d{4})\s*\|\s*(\d{1,2}:\d{2})\s+WIB\b/iu', trim($text), $match)) {
            return null;
        }
        $months = ['jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04', 'mei' => '05', 'jun' => '06', 'jul' => '07', 'agu' => '08', 'sep' => '09', 'okt' => '10', 'nov' => '11', 'des' => '12'];
        $month = $months[strtolower($match[2])] ?? null;
        if (! $month) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!d-m-Y H:i', sprintf('%02d-%02d-%04d %s', (int) $match[1], (int) $month, (int) $match[3], $match[4]), new DateTimeZone('Asia/Jakarta'));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format(DATE_ATOM);
    }

    private function isBnpbUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts) && ($parts['scheme'] ?? '') === 'https' && strtolower($parts['host'] ?? '') === 'www.bnpb.go.id'
            && ! isset($parts['user']) && ! isset($parts['pass']);
    }
}
