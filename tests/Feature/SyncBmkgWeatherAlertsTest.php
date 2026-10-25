<?php

namespace Tests\Feature;

use App\Models\EarlyWarning;
use App\Models\EarlyWarningSource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncBmkgWeatherAlertsTest extends TestCase
{
    use RefreshDatabase;

    private const string FEED_URL = 'https://www.bmkg.go.id/alerts/nowcast/id';

    private const string CAP_URL = 'https://www.bmkg.go.id/alerts/nowcast/id/CKR20261024008_alert.xml';

    public function test_active_weather_warning_is_imported_to_early_warnings_with_cap_metadata(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-25 01:00:00', 'Asia/Jakarta'));
        Http::preventStrayRequests();
        Http::fake([
            self::FEED_URL => Http::response($this->rss([$this->warning()]), 200),
            self::CAP_URL => Http::response($this->cap(), 200),
        ]);
        $author = User::factory()->create();

        $this->artisan('news:sync-bmkg-weather-alerts', ['--author-id' => $author->id])
            ->assertExitCode(Command::SUCCESS);

        $source = EarlyWarningSource::query()->where('source_identifier', 'CKR20261024008')->firstOrFail();
        $warning = $source->warning;
        $this->assertSame('Hujan Lebat disertai Petir di Kep. Riau', $warning->title);
        $this->assertSame('Severe', $warning->severity);
        $this->assertSame('Tanjungpinang; Bintan', $warning->affected_area);
        $this->assertSame('published', $warning->status);
        $this->assertSame('Immediate', $source->urgency);
        $this->assertSame(self::CAP_URL, $source->source_url);
        $this->assertDatabaseCount('early_warnings', 1);
        $this->assertDatabaseCount('news', 0);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::FEED_URL);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::CAP_URL);
    }

    public function test_repeated_sync_updates_the_same_warning_without_duplicates(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-25 01:00:00', 'Asia/Jakarta'));
        Http::preventStrayRequests();
        Http::fake([
            self::FEED_URL => Http::sequence()
                ->push($this->rss([$this->warning()]))
                ->push($this->rss([$this->warning('Pembaruan cuaca Kep. Riau')])),
            self::CAP_URL => Http::sequence()
                ->push($this->cap())
                ->push($this->cap('Pembaruan cuaca Kep. Riau')),
        ]);
        $author = User::factory()->create();

        $this->artisan('news:sync-bmkg-weather-alerts', ['--author-id' => $author->id])->assertExitCode(Command::SUCCESS);
        $this->artisan('news:sync-bmkg-weather-alerts', ['--author-id' => $author->id])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('early_warnings', 1);
        $source = EarlyWarningSource::query()->where('source_identifier', 'CKR20261024008')->firstOrFail();
        $this->assertSame('Pembaruan cuaca Kep. Riau', $source->warning->title);
        $this->assertSame('published', $source->warning->status);
    }

    public function test_empty_valid_feed_marks_existing_bmkg_warnings_expired_without_deleting_them(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::FEED_URL => Http::response($this->rss([]))]);
        $author = User::factory()->create();
        $warning = $this->createWarning($author);

        $this->artisan('news:sync-bmkg-weather-alerts', ['--author-id' => $author->id])
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('early_warnings', ['id' => $warning->id, 'status' => 'expired']);
        $this->assertDatabaseCount('early_warnings', 1);
    }

    public function test_scheduled_sync_reuses_the_creator_of_an_existing_bmkg_warning(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-25 01:00:00', 'Asia/Jakarta'));
        config(['services.news.sync_author_id' => null]);
        Http::preventStrayRequests();
        Http::fake([
            self::FEED_URL => Http::response($this->rss([$this->warning()])),
            self::CAP_URL => Http::response($this->cap()),
        ]);
        $author = User::factory()->create();
        $this->createWarning($author);

        $this->artisan('news:sync-bmkg-weather-alerts')->assertExitCode(Command::SUCCESS);

        $importedSource = EarlyWarningSource::query()
            ->where('source_identifier', 'CKR20261024008')
            ->firstOrFail();
        $this->assertSame($author->id, $importedSource->warning->created_by);
    }

    public function test_failed_feed_or_cap_keeps_existing_warnings_unchanged(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-25 01:00:00', 'Asia/Jakarta'));
        Http::preventStrayRequests();
        Http::fake([
            self::FEED_URL => Http::sequence()
                ->push('unavailable', 503)
                ->push($this->rss([$this->warning()])),
            self::CAP_URL => Http::response('<alert><info>', 200),
        ]);
        $author = User::factory()->create();
        $warning = $this->createWarning($author);

        $this->artisan('news:sync-bmkg-weather-alerts', ['--author-id' => $author->id])->assertExitCode(Command::FAILURE);
        $this->artisan('news:sync-bmkg-weather-alerts', ['--author-id' => $author->id])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('early_warnings', ['id' => $warning->id, 'status' => 'published']);
        $this->assertDatabaseCount('early_warnings', 1);
    }

    private function createWarning(User $author): EarlyWarning
    {
        $warning = EarlyWarning::query()->create([
            'created_by' => $author->id,
            'title' => 'Peringatan lama',
            'disaster_type' => 'cuaca',
            'description' => 'Peringatan tersimpan.',
            'severity' => 'Severe',
            'affected_area' => 'Kepulauan Riau',
            'source' => 'BMKG - Peringatan Dini Cuaca',
            'expires_at' => now()->addHour(),
            'status' => 'published',
        ]);
        $warning->sourceMetadata()->create([
            'source_identifier' => 'OLD-ALERT',
            'source_url' => 'https://www.bmkg.go.id/alerts/nowcast/id/OLD-ALERT_alert.xml',
        ]);

        return $warning;
    }

    /** @param array<int, array{title: string, link: string, description: string, pubDate: string}> $items */
    private function rss(array $items): string
    {
        $itemXml = collect($items)->map(fn (array $item): string => sprintf(
            '<item><title>%s</title><link>%s</link><description>%s</description><pubDate>%s</pubDate></item>',
            htmlspecialchars($item['title'], ENT_XML1),
            htmlspecialchars($item['link'], ENT_XML1),
            htmlspecialchars($item['description'], ENT_XML1),
            htmlspecialchars($item['pubDate'], ENT_XML1),
        ))->implode('');

        return '<?xml version="1.0"?><rss><channel><title>BMKG</title>'.$itemXml.'</channel></rss>';
    }

    private function cap(?string $headline = null): string
    {
        $headline ??= 'Hujan Lebat disertai Petir di Kep. Riau';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<alert xmlns="urn:oasis:names:tc:emergency:cap:1.2">'
            .'<identifier>CKR20261024008</identifier><sent>2026-10-25T00:53:00+07:00</sent>'
            .'<status>Actual</status><scope>Public</scope><info>'
            .'<urgency>Immediate</urgency><severity>Severe</severity><headline>'.$headline.'</headline>'
            .'<description>Hujan lebat disertai petir di wilayah Kepulauan Riau.</description>'
            .'<effective>2026-10-25T00:53:00+07:00</effective><expires>2026-10-25T02:53:00+07:00</expires>'
            .'<area><areaDesc>Tanjungpinang</areaDesc></area><area><areaDesc>Bintan</areaDesc></area>'
            .'</info></alert>';
    }

    /** @return array{title: string, link: string, description: string, pubDate: string} */
    private function warning(?string $title = null): array
    {
        return [
            'title' => $title ?? 'Hujan Lebat disertai Petir di Kep. Riau',
            'link' => self::CAP_URL,
            'description' => 'Pratinjau RSS BMKG.',
            'pubDate' => 'Sun, 25 Oct 2026 00:53:00 +0700',
        ];
    }
}
