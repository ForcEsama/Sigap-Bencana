<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BnpbWeeklyIncidentService
{
    private const string LAYER_URL = 'https://gis.bnpb.go.id/server/rest/services/Kejadian_Bencana_Mingguan/MapServer/25';

    private const string QUERY_URL = self::LAYER_URL.'/query';

    public function __construct(private readonly ExternalFeedCache $cache) {}

    /** @return array{status: string, items: array<int, array<string, mixed>>, source_url: string, message: ?string, fetched_at: ?string} */
    public function incidents(): array
    {
        return $this->cache->remember('bnpb-weekly-incidents-v3', 900, fn (): array => $this->load());
    }

    /** @return array{status: string, items: array<int, array<string, mixed>>, source_url: string, message: ?string} */
    private function load(): array
    {
        $response = Http::connectTimeout(4)
            ->timeout(8)
            ->acceptJson()
            ->get(self::QUERY_URL, [
                'where' => '1=1',
                'outFields' => 'objectid,ESRI_OID,dt,tgl,bulan,tahun,kabupaten,kategori_bencana,jumlah_kejadian,deskripsi,kronologis,kondisi_mutakhir',
                'orderByFields' => 'dt DESC',
                'resultRecordCount' => 100,
                'returnGeometry' => 'false',
                'f' => 'json',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('BNPB ArcGIS returned HTTP '.$response->status());
        }

        $payload = $response->json();
        if (! is_array($payload) || isset($payload['error']) || ! is_array($payload['features'] ?? null)) {
            throw new RuntimeException('BNPB ArcGIS returned an invalid feature response.');
        }

        $items = [];
        foreach ($payload['features'] as $feature) {
            $attributes = $feature['attributes'] ?? null;
            if (! is_array($attributes)) {
                continue;
            }

            $id = filter_var(
                $this->attribute($attributes, 'objectid') ?? $this->attribute($attributes, 'ESRI_OID'),
                FILTER_VALIDATE_INT,
            );
            $category = trim((string) ($this->attribute($attributes, 'kategori_bencana') ?? ''));
            $region = trim((string) ($this->attribute($attributes, 'kabupaten') ?? ''));
            $date = $this->dateFromMilliseconds($this->attribute($attributes, 'dt'))
                ?? $this->dateFromCalendarFields($attributes);
            if ($id === false || $id === null || $category === '' || $region === '' || $date === null) {
                continue;
            }

            $description = array_values(array_filter([
                trim((string) ($this->attribute($attributes, 'deskripsi') ?? '')),
                trim((string) ($this->attribute($attributes, 'kronologis') ?? '')),
                trim((string) ($this->attribute($attributes, 'kondisi_mutakhir') ?? '')),
            ], static fn (string $value): bool => $value !== ''));

            $items[] = [
                'id' => $id,
                'title' => $category.' di '.$region,
                'disaster_type' => $category,
                'description' => implode("\n\n", array_unique($description)),
                'affected_area' => $region,
                'published_at' => $date,
                'incident_count' => is_numeric($this->attribute($attributes, 'jumlah_kejadian'))
                    ? (int) $this->attribute($attributes, 'jumlah_kejadian')
                    : null,
                'source' => 'BNPB - Kejadian Bencana Mingguan',
                'source_url' => self::LAYER_URL,
                'status' => 'reported',
                'kind' => 'incident',
            ];
        }

        return [
            'status' => 'available',
            'items' => $items,
            'records_returned' => count($payload['features']),
            'records_mapped' => count($items),
            'source_url' => self::LAYER_URL,
            'message' => null,
        ];
    }

    private function dateFromMilliseconds(mixed $milliseconds): ?string
    {
        if (is_string($milliseconds) && trim($milliseconds) !== '' && ! is_numeric($milliseconds)) {
            try {
                return (new DateTimeImmutable($milliseconds))->format(DATE_ATOM);
            } catch (\Throwable) {
                return null;
            }
        }
        if (! is_numeric($milliseconds)) {
            return null;
        }

        try {
            return (new DateTimeImmutable('@'.(int) floor(((float) $milliseconds) / 1000)))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(DATE_ATOM);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $attributes */
    private function dateFromCalendarFields(array $attributes): ?string
    {
        $day = $this->attribute($attributes, 'tgl');
        $month = $this->attribute($attributes, 'bulan');
        $year = $this->attribute($attributes, 'tahun');
        if (! is_numeric($day) || ! is_numeric($month) || ! is_numeric($year)) {
            return null;
        }

        $date = sprintf('%04d-%02d-%02d 00:00:00', (int) $year, (int) $month, (int) $day);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, new \DateTimeZone('Asia/Jakarta'));

        return $parsed === false ? null : $parsed->format(DATE_ATOM);
    }

    /** @param array<string, mixed> $attributes */
    private function attribute(array $attributes, string $name): mixed
    {
        foreach ($attributes as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
