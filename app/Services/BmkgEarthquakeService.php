<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BmkgEarthquakeService
{
    private const SOURCES = [
        'latest' => 'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json',
        'magnitude_5_plus' => 'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json',
        'felt' => 'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json',
    ];

    public function __construct(private readonly ExternalFeedCache $cache) {}

    public function earthquakes(): array
    {
        return $this->cache->remember('bmkg-earthquakes', 300, fn (): array => $this->load());
    }

    private function load(): array
    {
        $items = [];
        $partial = false;
        foreach (self::SOURCES as $list => $url) {
            try {
                $response = Http::timeout(8)->connectTimeout(4)->withOptions(['allow_redirects' => false])->acceptJson()->get($url);
                if (! $response->successful()) {
                    throw new RuntimeException('HTTP '.$response->status());
                }
                $records = $response->json('Infogempa.gempa');
                if (! is_array($records)) {
                    throw new RuntimeException('Skema JSON BMKG tidak sesuai.');
                }
                if (isset($records['DateTime'])) {
                    $records = [$records];
                }
                foreach ($records as $record) {
                    $item = $this->mapRecord($record, $list, $url);
                    if ($item === null) {
                        $partial = true;

                        continue;
                    }
                    $existingImage = $items[$item['event_key']]['image_url'] ?? null;
                    if ($item['image_url'] === null && $existingImage !== null) {
                        $item['image_url'] = $existingImage;
                    }
                    $items[$item['event_key']] = $item;
                }
            } catch (\Throwable) {
                $partial = true;
            }
        }
        if (! $items) {
            throw new RuntimeException('Tidak ada record gempa yang dapat dibaca dari BMKG.');
        }
        $items = array_values($items);
        usort($items, static fn (array $a, array $b): int => strcmp($b['occurred_at'], $a['occurred_at']));

        return ['status' => $partial ? 'partial' : 'available', 'items' => array_slice($items, 0, 25), 'source_urls' => array_values(self::SOURCES)];
    }

    private function mapRecord(mixed $record, string $list, string $sourceUrl): ?array
    {
        if (! is_array($record)) {
            return null;
        }
        foreach (['DateTime', 'Magnitude', 'Kedalaman', 'Wilayah', 'Coordinates'] as $required) {
            if (! isset($record[$required]) || ! is_scalar($record[$required]) || trim((string) $record[$required]) === '') {
                return null;
            }
        }
        try {
            $occurred = (new DateTimeImmutable((string) $record['DateTime']))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format(DATE_ATOM);
        } catch (\Throwable) {
            return null;
        }
        $coordinates = array_map('trim', explode(',', (string) $record['Coordinates']));
        if (count($coordinates) !== 2 || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1]) || ! is_numeric($record['Magnitude'])) {
            return null;
        }
        $latitude = (float) $coordinates[0];
        $longitude = (float) $coordinates[1];
        if (abs($latitude) > 90 || abs($longitude) > 180) {
            return null;
        }
        $key = $occurred.'|'.$latitude.'|'.$longitude;
        $felt = trim((string) ($record['Dirasakan'] ?? ''));
        $shakemap = trim((string) ($record['Shakemap'] ?? ''));
        $imageUrl = preg_match('/^\d{14}\.mmi\.jpg$/D', $shakemap)
            ? 'https://data.bmkg.go.id/DataMKG/TEWS/'.$shakemap
            : null;

        return ['kind' => 'earthquake', 'type' => 'Gempa bumi', 'region' => trim((string) $record['Wilayah']),
            'title' => 'Gempa M '.$record['Magnitude'], 'summary' => trim((string) $record['Wilayah']), 'occurred_at' => $occurred,
            'magnitude' => (string) $record['Magnitude'], 'depth' => (string) $record['Kedalaman'], 'latitude' => $latitude,
            'longitude' => $longitude, 'felt' => $felt !== '' ? $felt : null,
            'tsunami_potential' => isset($record['Potensi']) ? (string) $record['Potensi'] : null,
            'image_url' => $imageUrl, 'source' => 'BMKG', 'source_url' => $sourceUrl, 'source_list' => $list, 'event_key' => $key];
    }
}
