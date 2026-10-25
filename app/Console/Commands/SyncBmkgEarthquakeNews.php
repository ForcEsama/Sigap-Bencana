<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

#[Signature('news:sync-bmkg-earthquakes {--author-id=}')]
#[Description('Synchronize recent M5.0+ earthquake records from BMKG open data.')]
class SyncBmkgEarthquakeNews extends Command
{
    private const string FEED_URL = 'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json';

    private const string SOURCE_URL = 'https://data.bmkg.go.id/gempabumi/';

    private const string SOURCE_NAME = 'BMKG - Data Terbuka';

    public function handle(): int
    {
        $authorId = (int) ($this->option('author-id') ?: config('services.news.sync_author_id'));

        if ($authorId < 1 || ! User::query()->whereKey($authorId)->exists()) {
            $this->error('Set NEWS_SYNC_AUTHOR_ID ke ID pengguna yang valid atau gunakan --author-id.');

            return self::FAILURE;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->get(self::FEED_URL);
        } catch (ConnectionException $exception) {
            report($exception);
            $this->error('Feed BMKG tidak dapat dihubungi. Data yang sudah tersimpan tidak diubah.');

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error("Feed BMKG merespons HTTP {$response->status()}. Data yang sudah tersimpan tidak diubah.");

            return self::FAILURE;
        }

        $events = data_get($response->json(), 'Infogempa.gempa');

        if (! is_array($events)) {
            $this->error('Format feed BMKG tidak sesuai dengan katalog gempabumi yang diharapkan.');

            return self::FAILURE;
        }

        $timestamp = CarbonImmutable::now();
        $articles = [];
        $skipped = 0;

        foreach ($events as $event) {
            $article = is_array($event) ? $this->mapEventToArticle($event, $authorId, $timestamp) : null;

            if ($article === null) {
                $skipped++;

                continue;
            }

            $articles[] = $article;
        }

        if ($articles !== []) {
            News::query()->upsert(
                $articles,
                ['slug'],
                [
                    'author_id',
                    'title',
                    'excerpt',
                    'content',
                    'image_path',
                    'category',
                    'source',
                    'source_url',
                    'status',
                    'published_at',
                    'updated_at',
                ],
            );
        }

        $this->info(sprintf('%d catatan BMKG disinkronkan; %d catatan tidak valid dilewati.', count($articles), $skipped));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>|null
     */
    private function mapEventToArticle(array $event, int $authorId, CarbonImmutable $timestamp): ?array
    {
        $dateTime = $event['DateTime'] ?? null;
        $magnitude = $event['Magnitude'] ?? null;
        $region = trim((string) ($event['Wilayah'] ?? ''));

        if (! is_string($dateTime) || ! is_numeric($magnitude) || (float) $magnitude < 5 || $region === '') {
            return null;
        }

        try {
            $eventTimestamp = CarbonImmutable::parse($dateTime);
            $eventDate = $eventTimestamp->setTimezone(config('app.timezone'));
        } catch (\InvalidArgumentException) {
            return null;
        }

        $magnitudeLabel = trim((string) $magnitude);
        $depth = trim((string) ($event['Kedalaman'] ?? ''));
        $coordinates = trim((string) ($event['Coordinates'] ?? ''));
        $tsunamiPotential = trim((string) ($event['Potensi'] ?? ''));
        $eventDateLabel = trim((string) ($event['Tanggal'] ?? $eventDate->format('d M Y')));
        $eventTimeLabel = trim((string) ($event['Jam'] ?? $eventDate->format('H:i:s T')));

        $details = [
            'Waktu kejadian: '.$eventDateLabel.' '.$eventTimeLabel,
            'Wilayah: '.$region,
            'Magnitudo: M'.$magnitudeLabel,
        ];

        if ($depth !== '') {
            $details[] = 'Kedalaman: '.$depth;
        }

        if ($coordinates !== '') {
            $details[] = 'Koordinat: '.$coordinates;
        }

        if ($tsunamiPotential !== '') {
            $details[] = 'Potensi tsunami: '.$tsunamiPotential;
        }

        $details[] = 'Sumber data: BMKG Data Terbuka.';
        $details[] = 'Parameter awal gempabumi dapat diperbarui oleh BMKG.';

        $slug = 'bmkg-gempa-'.$eventTimestamp->utc()->format('YmdHis');

        return [
            'author_id' => $authorId,
            'title' => Str::limit('BMKG: Gempa M'.$magnitudeLabel.' - '.$region, 250, ''),
            'slug' => $slug,
            'excerpt' => 'BMKG mencatat gempabumi M'.$magnitudeLabel.' di '.$region.' pada '.$eventDateLabel.' '.$eventTimeLabel.'.',
            'content' => implode("\n\n", $details),
            'image_path' => $this->shakemapUrl($event['Shakemap'] ?? null),
            'category' => 'Berita',
            'source' => self::SOURCE_NAME,
            'source_url' => self::SOURCE_URL,
            'status' => 'published',
            'published_at' => $eventDate->toDateTimeString(),
            'created_at' => $timestamp->toDateTimeString(),
            'updated_at' => $timestamp->toDateTimeString(),
        ];
    }

    private function shakemapUrl(mixed $shakemap): ?string
    {
        if (! is_string($shakemap)) {
            return null;
        }

        $fileName = basename(trim($shakemap));

        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.jpg\z/i', $fileName) || str_contains($fileName, '..')) {
            return null;
        }

        return 'https://static.bmkg.go.id/'.$fileName;
    }
}
