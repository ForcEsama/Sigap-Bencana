<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsEducationTest extends TestCase
{
    private string $temporaryPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sigap-news-education-'.bin2hex(random_bytes(6));
        (new Filesystem)->ensureDirectoryExists($this->temporaryPath.DIRECTORY_SEPARATOR.'cache');
        (new Filesystem)->ensureDirectoryExists($this->temporaryPath.DIRECTORY_SEPARATOR.'views');
        config([
            'cache.stores.file.path' => $this->temporaryPath.DIRECTORY_SEPARATOR.'cache',
            'cache.stores.file.lock_path' => $this->temporaryPath.DIRECTORY_SEPARATOR.'cache',
            'view.compiled' => $this->temporaryPath.DIRECTORY_SEPARATOR.'views',
            'logging.default' => 'null',
        ]);
        Cache::purge('file');
    }

    protected function tearDown(): void
    {
        Cache::purge('file');
        (new Filesystem)->deleteDirectory($this->temporaryPath);
        parent::tearDown();
    }

    public function test_guest_can_view_news_and_education(): void
    {
        $this->fakeSources();

        $this->get(route('news-education.index'))
            ->assertOk()
            ->assertSee('Berita & Edukasi');
    }

    public function test_category_tabs_filter_verified_news_and_education(): void
    {
        $this->fakeSources();
        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('news-education.index', ['category' => 'news']))
            ->assertOk()
            ->assertSee('BNPB: Kesiapsiagaan Masyarakat')
            ->assertSee('news%20thumbnail.jpg')
            ->assertSee('Berita Terkini')
            ->assertDontSee('Mitigasi Menghadapi Gempa Bumi');

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('news-education.index', ['category' => 'safety']))
            ->assertOk()
            ->assertSee('Mitigasi Menghadapi Gempa Bumi')
            ->assertDontSee('BNPB: Kesiapsiagaan Masyarakat');

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('news-education.index', ['category' => 'infographic']))
            ->assertOk()
            ->assertSee('Poster Informasi Peringatan Dini Tsunami')
            ->assertDontSee('Mitigasi Menghadapi Gempa Bumi');
    }

    public function test_external_detail_links_to_the_verified_original_source(): void
    {
        $this->fakeSources();
        $id = hash('sha256', 'bnpb|https://www.bnpb.go.id/berita/kesiapsiagaan-masyarakat');

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('news-education.show', ['source' => 'bnpb', 'item' => $id]))
            ->assertOk()
            ->assertSee('BNPB: Kesiapsiagaan Masyarakat')
            ->assertSee('https://www.bnpb.go.id/berita/kesiapsiagaan-masyarakat')
            ->assertSee('Baca sumber asli');
    }

    public function test_invalid_image_content_type_excludes_card(): void
    {
        Cache::purge('file');
        Http::fake([
            'https://www.bnpb.go.id/berita' => Http::response(str_replace('news thumbnail.jpg', 'bad-news.jpg', $this->bnpbListing()), 200),
            'https://www.bnpb.go.id/berita/kesiapsiagaan-masyarakat' => Http::response($this->bnpbArticle(), 200),
            'https://www.bnpb.go.id/storage/app/media/bad-news.jpg' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
            'https://www.bmkg.go.id/gempabumi/mitigasi/edukasi-gempabumi-tsunami' => Http::response($this->bmkgPage(), 200),
            'https://i0.wp.com/content.bmkg.go.id/wp-content/uploads/mitigasi-gempa.jpg?ssl=1' => Http::response('', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->actingAs(new User(['name' => 'Masyarakat']))
            ->get(route('news-education.index', ['category' => 'news']))
            ->assertOk()
            ->assertSee('Belum ada konten bergambar yang tersedia')
            ->assertDontSee('BNPB: Kesiapsiagaan Masyarakat');

        Http::assertSent(fn ($request): bool => $request->method() === 'HEAD' && str_contains($request->url(), 'bad-news.jpg'));
    }

    private function fakeSources(): void
    {
        Http::fake([
            'https://www.bnpb.go.id/berita' => Http::response($this->bnpbListing(), 200),
            'https://www.bnpb.go.id/berita/kesiapsiagaan-masyarakat' => Http::response($this->bnpbArticle(), 200),
            'https://www.bnpb.go.id/storage/app/media/news%20thumbnail.jpg' => Http::response('', 200, ['Content-Type' => 'image/jpeg']),
            'https://www.bmkg.go.id/gempabumi/mitigasi/edukasi-gempabumi-tsunami' => Http::response($this->bmkgPage(), 200),
            'https://i0.wp.com/content.bmkg.go.id/wp-content/uploads/mitigasi-gempa.jpg?ssl=1' => Http::response('', 200, ['Content-Type' => 'image/jpeg']),
            'https://i0.wp.com/content.bmkg.go.id/wp-content/uploads/poster-tsunami.jpg?ssl=1' => Http::response('', 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    private function bnpbListing(): string
    {
        return '<div class="col-md-4"><img alt="BNPB: Kesiapsiagaan Masyarakat" src="https://www.bnpb.go.id/storage/app/media/news thumbnail.jpg"><div class="date">25 Okt 2026 | 09:15 WIB</div><a class="tracking" data-type="news" href="https://www.bnpb.go.id/berita/kesiapsiagaan-masyarakat">BNPB: Kesiapsiagaan Masyarakat</a></div>';
    }

    private function bnpbArticle(): string
    {
        return '<html><head><meta property="og:title" content="BNPB: Kesiapsiagaan Masyarakat"><meta property="og:description" content="BNPB membahas kesiapsiagaan masyarakat."></head></html>';
    }

    private function bmkgPage(): string
    {
        return '<html><body><div class="card"><img alt="Mitigasi Menghadapi Gempa Bumi" src="https://i0.wp.com/content.bmkg.go.id/wp-content/uploads/mitigasi-gempa.jpg?ssl=1"><h3>Mitigasi Menghadapi Gempa Bumi</h3><p>Panduan mitigasi sebelum, saat, dan setelah gempa.</p><a href="https://www.bmkg.go.id/gempabumi/mitigasi/edukasi-gempabumi-tsunami">Pelajari</a></div><div class="card"><img alt="Poster Informasi Peringatan Dini Tsunami" src="https://i0.wp.com/content.bmkg.go.id/wp-content/uploads/poster-tsunami.jpg?ssl=1"><h3>Poster Informasi Peringatan Dini Tsunami</h3><p>Poster mengenai tahapan peringatan dini tsunami dari BMKG.</p><a href="https://content.bmkg.go.id/poster-tsunami.pdf">Unduh Poster</a></div></body></html>';
    }
}
