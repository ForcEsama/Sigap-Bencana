<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BmkgEarthquakeService;
use App\Services\BmkgNowcastService;
use App\Services\BnpbNewsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DisasterAlertFeedsTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        config(['logging.default' => 'null']);
        $this->cachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sigap-feed-cache-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->cachePath.DIRECTORY_SEPARATOR.'cache');
        File::ensureDirectoryExists($this->cachePath.DIRECTORY_SEPARATOR.'views');
        config([
            'cache.stores.file.path' => $this->cachePath.DIRECTORY_SEPARATOR.'cache',
            'cache.stores.file.lock_path' => $this->cachePath.DIRECTORY_SEPARATOR.'cache',
            'view.compiled' => $this->cachePath.DIRECTORY_SEPARATOR.'views',
        ]);
        Cache::purge('file');
    }

    protected function tearDown(): void
    {
        Cache::purge('file');
        File::deleteDirectory($this->cachePath);
        parent::tearDown();
    }

    public function test_warning_page_shows_only_active_official_warnings(): void
    {
        $this->fakeAllSources();

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index'))
            ->assertOk()
            ->assertSee('Peringatan resmi BMKG')
            ->assertSee('Peringatan Hujan dari BMKG')
            ->assertDontSee('Kejadian Gempa BMKG')
            ->assertDontSee('Berita BNPB')
            ->assertDontSee('Artikel BNPB Terverifikasi')
            ->assertSee('https://www.bmkg.go.id/alerts/nowcast/id/TEST_alert.xml')
            ->assertDontSee('https://data.bmkg.go.id/DataMKG/TEWS/20261022175749.mmi.jpg')
            ->assertSee('Lihat Detail')
            ->assertSee('name="q"', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="region"', false)
            ->assertDontSee('Severity Moderate')
            ->assertDontSee('Risiko Tinggi')
            ->assertDontSee('Dalam Penanganan');
    }

    public function test_warning_page_does_not_add_an_unverified_local_illustration(): void
    {
        $this->fakeAllSources(event: 'Tsunami', headline: 'Peringatan Tsunami dari BMKG');

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index'))
            ->assertOk()
            ->assertSee('Peringatan Tsunami dari BMKG')
            ->assertDontSee('Ilustrasi')
            ->assertDontSee('dokumentasi tsunami Aceh 2004');
    }

    public function test_search_type_and_region_filters_apply_to_available_source_fields(): void
    {
        $this->fakeAllSources();

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index', ['q' => 'Wilayah Uji']))
            ->assertOk()
            ->assertSee('Wilayah Uji')
            ->assertDontSee('Artikel BNPB Terverifikasi');

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index', ['type' => 'Hujan Lebat', 'region' => 'Wilayah Uji']))
            ->assertOk()
            ->assertSee('Peringatan Hujan dari BMKG')
            ->assertDontSee('Artikel BNPB Terverifikasi')
            ->assertDontSee('Gempa M 5.2');
    }

    public function test_page_renders_a_simple_empty_state_when_sources_are_unavailable(): void
    {
        $capUrl = 'https://www.bmkg.go.id/alerts/nowcast/id/expired_alert.xml';
        $cap = '<alert xmlns="urn:oasis:names:tc:emergency:cap:1.2"><status>Actual</status><msgType>Alert</msgType><scope>Public</scope><info><event>Hujan</event><headline>Peringatan lama</headline><effective>'.now()->subDays(2)->toIso8601String().'</effective><expires>'.now()->subDay()->toIso8601String().'</expires></info></alert>';
        Http::fake([
            'https://www.bmkg.go.id/alerts/nowcast/id' => Http::response('<rss><channel><item><link>'.$capUrl.'</link></item></channel></rss>', 200),
            $capUrl => Http::response($cap, 200),
            'https://data.bmkg.go.id/DataMKG/TEWS/*' => Http::response(['Infogempa' => ['gempa' => []]], 200),
            'https://www.bnpb.go.id/berita' => Http::response('<html></html>', 200),
            '*' => fn () => throw new ConnectionException('simulated timeout'),
        ]);

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index'))
            ->assertOk()
            ->assertSee('Peringatan resmi belum dapat dimuat.')
            ->assertDontSee('Peringatan lama');
    }

    public function test_expired_cap_alert_is_not_shown_as_active(): void
    {
        $this->fakeAllSources(expiresAt: now()->subMinute()->toIso8601String());

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index'))
            ->assertOk()
            ->assertDontSee('Kejadian Gempa BMKG')
            ->assertDontSee('Peringatan Hujan dari BMKG');
    }

    public function test_invalid_feed_format_and_network_failure_show_unavailable_state_without_fabricated_items(): void
    {
        Http::fake([
            'https://www.bmkg.go.id/alerts/nowcast/id' => Http::response('<rss><broken>', 200),
            'https://data.bmkg.go.id/DataMKG/TEWS/*' => Http::response('{"wrong":true}', 200, ['Content-Type' => 'application/json']),
            'https://www.bnpb.go.id/berita' => Http::response('<html><title>Format berubah</title></html>', 200),
            '*' => fn () => throw new ConnectionException('simulated timeout'),
        ]);

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('disaster-alerts.index'))
            ->assertOk()
            ->assertSee('Peringatan resmi belum dapat dimuat.')
            ->assertDontSee('Artikel BNPB Terverifikasi');
    }

    public function test_bnpb_links_from_source_html_are_restricted_to_the_official_host(): void
    {
        $listing = '<div class="berita-terkini"><div class="col-md-4"><img alt="Link palsu" src="https://evil.example/image.jpg"><div class="date">24 Okt 2026 | 09:15 WIB</div><a class="tracking" data-type="news" href="https://evil.example/berita/palsu">Link palsu</a></div></div>';
        Http::fake(['https://www.bnpb.go.id/berita' => Http::response($listing, 200)]);

        $result = app(BnpbNewsService::class)->news();

        $this->assertSame('unavailable', $result['status']);
        $this->assertSame([], $result['items']);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'evil.example'));
    }

    public function test_bnpb_future_publication_date_is_not_displayed_as_published_news(): void
    {
        $url = 'https://www.bnpb.go.id/berita/masa-depan';
        $listing = '<div class="berita-terkini"><div class="col-md-4"><img alt="Artikel bertanggal masa depan" src="https://www.bnpb.go.id/image.jpg"><div class="date">31 Des 2099 | 09:15 WIB</div><a class="tracking" data-type="news" href="'.$url.'">Artikel bertanggal masa depan</a></div></div>';
        Http::fake(['https://www.bnpb.go.id/berita' => Http::response($listing, 200)]);

        $result = app(BnpbNewsService::class)->news();

        $this->assertSame('unavailable', $result['status']);
        $this->assertSame([], $result['items']);
        Http::assertNotSent(fn ($request): bool => $request->url() === $url);
    }

    public function test_bmkg_cap_xml_with_doctype_is_rejected_without_external_entity_resolution(): void
    {
        $capUrl = 'https://www.bmkg.go.id/alerts/nowcast/id/XXE_alert.xml';
        Http::fake([
            'https://www.bmkg.go.id/alerts/nowcast/id' => Http::response('<rss><channel><item><link>'.$capUrl.'</link></item></channel></rss>', 200),
            $capUrl => Http::response('<!DOCTYPE alert [<!ENTITY test SYSTEM "file:///not-a-real-file">]><alert>&test;</alert>', 200),
        ]);

        $result = app(BmkgNowcastService::class)->warnings();

        $this->assertSame('partial', $result['status']);
        $this->assertSame([], $result['items']);
    }

    public function test_timed_out_bmkg_requests_return_unavailable_without_failing_the_page(): void
    {
        Http::fake(fn () => throw new ConnectionException('simulated timeout'));

        $result = app(BmkgEarthquakeService::class)->earthquakes();

        $this->assertSame('unavailable', $result['status']);
        $this->assertSame([], $result['items']);
    }

    public function test_earthquake_image_url_only_uses_valid_shakemap_filename(): void
    {
        $record = ['DateTime' => now()->toIso8601String(), 'Coordinates' => '-3.11,102.14', 'Magnitude' => '5.2', 'Kedalaman' => '120 km', 'Wilayah' => 'Lebong', 'Shakemap' => '../../evil.jpg'];
        Http::fake([
            'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json' => Http::response(['Infogempa' => ['gempa' => $record]], 200),
            'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json' => Http::response(['Infogempa' => ['gempa' => []]], 200),
            'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json' => Http::response(['Infogempa' => ['gempa' => []]], 200),
        ]);

        $result = app(BmkgEarthquakeService::class)->earthquakes();

        $this->assertSame('available', $result['status']);
        $this->assertNull($result['items'][0]['image_url']);
    }

    public function test_stale_warning_cache_is_rechecked_against_cap_expiry(): void
    {
        $expired = now()->subMinute()->toIso8601String();
        Cache::store('file')->put('sigap:feed:last-success:bmkg-nowcast-id', [
            'status' => 'available',
            'fetched_at' => now()->subDay()->toIso8601String(),
            'items' => [[
                'effective' => now()->subDay()->toIso8601String(),
                'expires' => $expired,
                'title' => 'Peringatan lama',
            ]],
        ], now()->addHour());
        Http::fake(fn () => throw new ConnectionException('simulated timeout'));

        $result = app(BmkgNowcastService::class)->warnings();

        $this->assertSame('stale', $result['status']);
        $this->assertSame([], $result['items']);
    }

    private function fakeAllSources(?string $expiresAt = null, string $event = 'Hujan Lebat', string $headline = 'Peringatan Hujan dari BMKG'): void
    {
        $effectiveAt = now()->subMinutes(2)->toIso8601String();
        $expiresAt ??= now()->addHour()->toIso8601String();
        $capUrl = 'https://www.bmkg.go.id/alerts/nowcast/id/TEST_alert.xml';
        $rss = '<rss version="2.0"><channel><item><title>Hujan</title><link>'.$capUrl.'</link></item></channel></rss>';
        $cap = '<alert xmlns="urn:oasis:names:tc:emergency:cap:1.2"><identifier>test-1</identifier><sender>bmkg</sender><sent>'.now()->toIso8601String().'</sent><status>Actual</status><msgType>Alert</msgType><scope>Public</scope><info><category>Met</category><event>'.$event.'</event><headline>'.$headline.'</headline><description>Hujan terpantau di wilayah uji.</description><effective>'.$effectiveAt.'</effective><expires>'.$expiresAt.'</expires><severity>Moderate</severity><urgency>Immediate</urgency><certainty>Observed</certainty><area><areaDesc>Wilayah Uji</areaDesc></area></info></alert>';
        $record = ['Tanggal' => '25 Okt 2026', 'Jam' => '09:00:00 WIB', 'DateTime' => now()->toIso8601String(), 'Coordinates' => '-3.11,102.14', 'Magnitude' => '5.2', 'Kedalaman' => '120 km', 'Wilayah' => 'Pusat gempa berada di darat 6 km BaratLaut Lebong', 'Potensi' => 'Tidak berpotensi tsunami', 'Dirasakan' => 'III Kota Bengkulu', 'Shakemap' => '20261022175749.mmi.jpg'];
        $newsUrl = 'https://www.bnpb.go.id/berita/artikel-uji';
        $listing = '<div class="berita-terkini"><div class="col-md-4 col-12"><div class="block"><img alt="Artikel BNPB Terverifikasi" src="https://www.bnpb.go.id/storage/app/media/test.jpg"><div class="date">24 Okt 2026 | 09:15 WIB</div><div class="title"><a class="tracking" data-type="news" href="'.$newsUrl.'">Artikel BNPB Terverifikasi</a></div></div></div></div>';
        $article = '<html><head><meta property="og:title" content="Artikel BNPB Terverifikasi"><meta property="og:description" content="&lt;p&gt;Ringkasan terbitan BNPB.&lt;/p&gt;"></head></html>';

        Http::fake([
            'https://www.bmkg.go.id/alerts/nowcast/id' => Http::response($rss, 200, ['Content-Type' => 'application/rss+xml']),
            $capUrl => Http::response($cap, 200, ['Content-Type' => 'application/xml']),
            'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json' => Http::response(['Infogempa' => ['gempa' => $record]], 200),
            'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json' => Http::response(['Infogempa' => ['gempa' => [$record]]], 200),
            'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json' => Http::response(['Infogempa' => ['gempa' => [$record]]], 200),
            'https://www.bnpb.go.id/berita' => Http::response($listing, 200),
            $newsUrl => Http::response($article, 200),
        ]);
    }
}
