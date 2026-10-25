<?php

namespace App\Console\Commands;

use App\Models\EarlyWarning;
use App\Models\EarlyWarningSource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

#[Signature('news:sync-bmkg-weather-alerts {--author-id=}')]
#[Description('Synchronize active BMKG weather warnings from its official RSS and CAP feeds.')]
class SyncBmkgWeatherAlerts extends Command
{
    private const string FEED_URL = 'https://www.bmkg.go.id/alerts/nowcast/id';

    private const string SOURCE_NAME = 'BMKG - Peringatan Dini Cuaca';

    public function handle(): int
    {
        $authorId = (int) (
            $this->option('author-id')
            ?: config('services.news.sync_author_id')
            ?: EarlyWarning::query()
                ->where('source', self::SOURCE_NAME)
                ->whereNotNull('created_by')
                ->value('created_by')
        );

        if ($authorId < 1 || ! User::query()->whereKey($authorId)->exists()) {
            $this->error('Set NEWS_SYNC_AUTHOR_ID ke ID pengguna yang valid atau gunakan --author-id.');

            return self::FAILURE;
        }

        try {
            $response = Http::accept('application/rss+xml, application/xml, text/xml')
                ->connectTimeout(5)
                ->timeout(15)
                ->get(self::FEED_URL);
        } catch (ConnectionException $exception) {
            report($exception);
            $this->error('Feed RSS BMKG gagal dihubungi. Data tersimpan tidak diubah.');

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error("Feed RSS BMKG merespons HTTP {$response->status()}. Data tersimpan tidak diubah.");

            return self::FAILURE;
        }

        $feed = $this->parseXml($response->body());

        if ($feed === null || ! isset($feed->channel)) {
            $this->error('RSS BMKG tidak berisi struktur channel yang valid. Data tersimpan tidak diubah.');

            return self::FAILURE;
        }

        $timestamp = CarbonImmutable::now();
        $warnings = [];
        $skipped = 0;

        foreach ($feed->channel->item as $item) {
            $sourceUrl = trim((string) ($item->link ?? ''));
            $identifier = $this->alertIdentifier($sourceUrl);

            if ($identifier === null) {
                $skipped++;

                continue;
            }

            try {
                $capResponse = Http::accept('application/xml, text/xml')
                    ->connectTimeout(5)
                    ->timeout(10)
                    ->get($sourceUrl);
            } catch (ConnectionException $exception) {
                report($exception);
                $skipped++;

                continue;
            }

            $cap = $capResponse->successful() ? $this->parseXml($capResponse->body()) : null;
            $warning = $cap instanceof SimpleXMLElement
                ? $this->mapCapToWarning($cap, $identifier, $sourceUrl, $authorId, $timestamp)
                : null;

            if ($warning === null) {
                $skipped++;

                continue;
            }

            $warnings[$identifier] = $warning;
        }

        DB::transaction(function () use ($warnings, $skipped, $timestamp): void {
            foreach ($warnings as $identifier => $record) {
                $source = EarlyWarningSource::query()->where('source_identifier', $identifier)->first();

                if ($source !== null) {
                    $source->warning()->update($record['warning']);
                    $source->update($record['metadata']);

                    continue;
                }

                $warning = EarlyWarning::query()->create($record['warning']);
                $warning->sourceMetadata()->create($record['metadata']);
            }

            if ($skipped === 0) {
                $this->archiveInactiveWarnings(array_keys($warnings), $timestamp);
            }
        });

        $this->info(sprintf('%d peringatan aktif disinkronkan; %d item tidak valid dilewati.', count($warnings), $skipped));

        return self::SUCCESS;
    }

    private function parseXml(string $body): ?SimpleXMLElement
    {
        $previousErrorMode = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        return $xml instanceof SimpleXMLElement ? $xml : null;
    }

    /**
     * @return array{warning: array<string, mixed>, metadata: array<string, mixed>}|null
     */
    private function mapCapToWarning(
        SimpleXMLElement $cap,
        string $identifier,
        string $sourceUrl,
        int $authorId,
        CarbonImmutable $timestamp,
    ): ?array {
        $capIdentifier = $this->firstValue($cap, 'identifier');
        $scope = $this->firstValue($cap, 'scope');
        $status = $this->firstValue($cap, 'status');
        $info = $this->firstNode($cap, 'info');

        if ($capIdentifier === '' || $scope !== 'Public' || $status !== 'Actual' || $info === null) {
            return null;
        }

        $headline = $this->firstValue($info, 'headline');
        $description = $this->firstValue($info, 'description');
        $severity = $this->firstValue($info, 'severity');
        $urgency = $this->firstValue($info, 'urgency');
        $effective = $this->parseDate($this->firstValue($info, 'effective'));
        $expires = $this->parseDate($this->firstValue($info, 'expires'));
        $publishedAt = $this->parseDate($this->firstValue($cap, 'sent'));
        $area = $this->areaDescription($info);

        if ($headline === '' || $description === '' || $area === '' || $expires === null
            || $expires->lessThanOrEqualTo($timestamp)) {
            return null;
        }

        $allowedSeverities = ['Extreme', 'Severe', 'Moderate', 'Minor', 'Unknown'];
        $severity = in_array($severity, $allowedSeverities, true) ? $severity : 'Unknown';

        return [
            'warning' => [
                'created_by' => $authorId,
                'title' => $headline,
                'disaster_type' => 'cuaca',
                'description' => $description,
                'severity' => $severity,
                'affected_area' => $area,
                'latitude' => null,
                'longitude' => null,
                'source' => self::SOURCE_NAME,
                'starts_at' => $effective?->toDateTimeString(),
                'expires_at' => $expires->toDateTimeString(),
                'status' => 'published',
                'updated_at' => $timestamp->toDateTimeString(),
            ],
            'metadata' => [
                'source_identifier' => $identifier,
                'source_url' => $sourceUrl,
                'source_published_at' => $publishedAt?->toDateTimeString(),
                'urgency' => $urgency !== '' ? $urgency : null,
            ],
        ];
    }

    private function firstValue(SimpleXMLElement $node, string $name): string
    {
        $matches = $node->xpath('.//*[local-name()="'.$name.'"]');

        return isset($matches[0]) ? trim((string) $matches[0]) : '';
    }

    private function firstNode(SimpleXMLElement $node, string $name): ?SimpleXMLElement
    {
        $matches = $node->xpath('.//*[local-name()="'.$name.'"]');

        return $matches[0] ?? null;
    }

    private function areaDescription(SimpleXMLElement $info): string
    {
        $areas = $info->xpath('.//*[local-name()="area"]/*[local-name()="areaDesc"]');

        return collect($areas ?: [])
            ->map(fn (SimpleXMLElement $area): string => trim((string) $area))
            ->filter()
            ->unique()
            ->implode('; ');
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function alertIdentifier(string $sourceUrl): ?string
    {
        $url = parse_url($sourceUrl);

        if (($url['scheme'] ?? null) !== 'https' || ($url['host'] ?? null) !== 'www.bmkg.go.id'
            || isset($url['query']) || isset($url['fragment'])
            || ! preg_match('#\A/alerts/nowcast/id/([A-Za-z0-9][A-Za-z0-9_-]{0,79})_alert\.xml\z#', $url['path'] ?? '', $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param  array<int, string>  $activeIdentifiers
     */
    private function archiveInactiveWarnings(array $activeIdentifiers, CarbonImmutable $timestamp): void
    {
        $query = EarlyWarning::query()
            ->where('source', self::SOURCE_NAME)
            ->where('status', 'published')
            ->whereHas('sourceMetadata');

        if ($activeIdentifiers !== []) {
            $query->whereDoesntHave('sourceMetadata', function ($sourceQuery) use ($activeIdentifiers): void {
                $sourceQuery->whereIn('source_identifier', $activeIdentifiers);
            });
        }

        $query->update([
            'status' => 'expired',
            'updated_at' => $timestamp->toDateTimeString(),
        ]);
    }
}
