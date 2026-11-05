<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BmkgFeltEarthquakeService
{
    private const string FEED_URL = 'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json';

    private const string SOURCE_URL = 'https://data.bmkg.go.id/gempabumi/';

    public function __construct(private readonly ExternalFeedCache $cache) {}

    /** @return array{status: string, items: array<int, array<string, mixed>>, source_url: string, message: ?string, fetched_at: ?string} */
    public function incidents(): array
    {
        return $this->cache->remember('bmkg-felt-earthquakes-v1', 300, fn (): array => $this->load());
    }

    /** @return array{status: string, items: array<int, array<string, mixed>>, source_url: string, message: ?string} */
    private function load(): array
    {
        $response = Http::connectTimeout(4)
            ->timeout(8)
            ->acceptJson()
            ->get(self::FEED_URL);

        if (! $response->successful()) {
            throw new RuntimeException('BMKG felt-earthquake feed returned HTTP '.$response->status());
        }

        $earthquakes = $response->json('Infogempa.gempa');
        if (! is_array($earthquakes)) {
            throw new RuntimeException('BMKG felt-earthquake feed has an invalid format.');
        }

        $items = [];
        foreach ($earthquakes as $earthquake) {
            if (! is_array($earthquake)) {
                continue;
            }

            $date = $this->date($earthquake['DateTime'] ?? null);
            $magnitude = trim((string) ($earthquake['Magnitude'] ?? ''));
            $region = trim((string) ($earthquake['Wilayah'] ?? ''));
            if ($date === null || ! is_numeric($magnitude) || $region === '') {
                continue;
            }

            $identifier = implode('|', [
                $earthquake['DateTime'],
                $earthquake['Coordinates'] ?? '',
                $magnitude,
                $region,
            ]);
            $description = array_values(array_filter([
                'Magnitudo '.$magnitude,
                ! empty($earthquake['Kedalaman']) ? 'Kedalaman '.$earthquake['Kedalaman'] : null,
                ! empty($earthquake['Dirasakan']) ? 'Dirasakan: '.$earthquake['Dirasakan'] : null,
            ]));

            $items[] = [
                'id' => (int) sprintf('%u', crc32($identifier)),
                'title' => 'Gempa bumi M'.$magnitude.' — '.$region,
                'disaster_type' => 'Gempa bumi',
                'description' => implode('. ', $description).'.',
                'affected_area' => $region,
                'published_at' => $date,
                'incident_count' => null,
                'source' => 'BMKG - Gempabumi Dirasakan',
                'source_url' => self::SOURCE_URL,
                'status' => 'reported',
                'kind' => 'incident',
            ];
        }

        return [
            'status' => 'available',
            'items' => $items,
            'records_returned' => count($earthquakes),
            'records_mapped' => count($items),
            'source_url' => self::SOURCE_URL,
            'message' => null,
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format(DATE_ATOM);
        } catch (\Throwable) {
            return null;
        }
    }
}
