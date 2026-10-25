<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncBmkgEarthquakeNewsTest extends TestCase
{
    use RefreshDatabase;

    private const string FEED_URL = 'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json';

    public function test_bmkg_events_are_imported_with_source_attribution_and_shakemap(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::FEED_URL => Http::response($this->feedPayload([
                $this->event(),
                ['DateTime' => 'invalid'],
            ])),
        ]);
        $author = User::factory()->create();

        $this->artisan('news:sync-bmkg-earthquakes', ['--author-id' => $author->id])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('news', [
            'slug' => 'bmkg-gempa-20261024105749',
            'author_id' => $author->id,
            'category' => 'Berita',
            'source' => 'BMKG - Data Terbuka',
            'source_url' => 'https://data.bmkg.go.id/gempabumi/',
            'image_path' => 'https://static.bmkg.go.id/20261024105749.mmi.jpg',
        ]);
        $this->assertDatabaseCount('news', 1);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::FEED_URL);
    }

    public function test_repeated_sync_updates_the_same_event_without_creating_duplicates(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::FEED_URL => Http::response($this->feedPayload([$this->event()]))]);
        $author = User::factory()->create();

        $this->artisan('news:sync-bmkg-earthquakes', ['--author-id' => $author->id])
            ->assertExitCode(Command::SUCCESS);
        $this->artisan('news:sync-bmkg-earthquakes', ['--author-id' => $author->id])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('news', 1);
        $this->assertDatabaseHas('news', [
            'slug' => 'bmkg-gempa-20261024105749',
            'title' => 'BMKG: Gempa M5.2 - Pusat gempa berada di darat 6 km BaratLaut Lebong',
        ]);
        Http::assertSentCount(2);
    }

    public function test_unavailable_bmkg_feed_leaves_existing_articles_untouched(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::FEED_URL => Http::response(['message' => 'Unavailable'], 503)]);
        $author = User::factory()->create();
        News::query()->create([
            'author_id' => $author->id,
            'title' => 'Artikel lokal yang sudah ada',
            'slug' => 'artikel-lokal-ada',
            'content' => 'Konten lokal.',
            'category' => 'Edukasi',
            'source' => 'Materi terkurasi',
            'source_url' => 'https://www.bnpb.go.id/',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->artisan('news:sync-bmkg-earthquakes', ['--author-id' => $author->id])
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('news', 1);
        $this->assertDatabaseHas('news', [
            'slug' => 'artikel-lokal-ada',
            'title' => 'Artikel lokal yang sudah ada',
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::FEED_URL);
    }

    public function test_connection_failure_does_not_create_news_records(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::FEED_URL => Http::failedConnection()]);
        $author = User::factory()->create();

        $this->artisan('news:sync-bmkg-earthquakes', ['--author-id' => $author->id])
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('news', 0);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::FEED_URL);
    }

    public function test_invalid_feed_shape_does_not_create_news_records(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::FEED_URL => Http::response(['unexpected' => []])]);
        $author = User::factory()->create();

        $this->artisan('news:sync-bmkg-earthquakes', ['--author-id' => $author->id])
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('news', 0);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::FEED_URL);
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<string, mixed>
     */
    private function feedPayload(array $events): array
    {
        return ['Infogempa' => ['gempa' => $events]];
    }

    /**
     * @return array<string, string>
     */
    private function event(): array
    {
        return [
            'Tanggal' => '24 Okt 2026',
            'Jam' => '17:57:49 WIB',
            'DateTime' => '2026-10-24T10:57:49+00:00',
            'Coordinates' => '-3.11,102.14',
            'Magnitude' => '5.2',
            'Kedalaman' => '120 km',
            'Wilayah' => 'Pusat gempa berada di darat 6 km BaratLaut Lebong',
            'Potensi' => 'Tidak berpotensi tsunami',
            'Shakemap' => '20261024105749.mmi.jpg',
        ];
    }
}
