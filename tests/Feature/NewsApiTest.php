<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use App\Services\NewsEducationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_returns_only_published_attributed_articles_that_are_due(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 24)->setTime(16, 0));
        $author = User::factory()->create();
        $this->createArticle($author, ['slug' => 'visible-article']);
        $this->createArticle($author, ['slug' => 'draft-article', 'status' => 'draft']);
        $this->createArticle($author, [
            'slug' => 'scheduled-article',
            'published_at' => now()->addMinute(),
        ]);
        $this->createArticle($author, [
            'slug' => 'unattributed-article',
            'source_url' => null,
        ]);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'visible-article')
            ->assertJsonPath('data.0.category', 'Berita')
            ->assertJsonPath('data.0.is_simulation', false);
    }

    public function test_public_feed_filters_articles_by_category(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, ['slug' => 'news-item']);
        $this->createArticle($author, [
            'slug' => 'education-item',
            'category' => 'Edukasi',
        ]);

        $this->getJson('/api/v1/news?category=edukasi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'education-item')
            ->assertJsonPath('data.0.category', 'Edukasi');
    }

    public function test_latest_news_filter_hides_simulations_while_all_feed_keeps_the_notice(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, ['slug' => 'real-news']);
        $this->createArticle($author, [
            'title' => 'Simulasi Berita: Contoh',
            'slug' => 'simulasi-berita-contoh',
            'source' => 'Contoh konten SIGAP BENCANA',
            'source_url' => 'https://www.bmkg.go.id/',
        ]);

        $this->getJson('/api/v1/news?category=berita')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'real-news')
            ->assertJsonPath('data.0.is_simulation', false);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_bmkg_weather_warning_is_excluded_from_news_lists_and_details(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, [
            'title' => 'Peringatan Dini Cuaca BMKG: Hujan Lebat disertai Petir di Kep. Riau',
            'slug' => 'bmkg-peringatan-cuaca-ckr20261024008',
            'category' => 'Peringatan',
            'content' => "Jenis informasi: Peringatan dini cuaca (CAP).\n\n"
                .'Informasi ini merupakan peringatan potensi cuaca, bukan laporan kejadian bencana.',
            'source' => 'BMKG - Peringatan Dini Cuaca',
            'source_url' => 'https://www.bmkg.go.id/alerts/nowcast/id/CKR20261024008_alert.xml',
        ]);

        $this->getJson('/api/v1/news?category=berita')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/news')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/news/bmkg-peringatan-cuaca-ckr20261024008')->assertNotFound();
    }

    public function test_public_article_detail_includes_content_and_simulation_notice(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, [
            'title' => 'Simulasi Berita: Contoh',
            'slug' => 'simulasi-contoh',
            'source' => 'Contoh konten SIGAP BENCANA',
            'content' => '<p>SIMULASI — bukan laporan kejadian aktual.</p>',
        ]);

        $this->getJson('/api/v1/news/simulasi-contoh')
            ->assertOk()
            ->assertJsonPath('data.is_simulation', true)
            ->assertJsonPath('data.content', '<p>SIMULASI — bukan laporan kejadian aktual.</p>')
            ->assertJsonMissingPath('data.author_id');
    }

    public function test_public_feed_rejects_an_unsupported_category(): void
    {
        $this->getJson('/api/v1/news?category=peringatan')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_public_detail_hides_a_draft_article(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, [
            'slug' => 'draft-detail',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/news/draft-detail')->assertNotFound();
    }

    public function test_public_feed_returns_a_url_for_an_image_in_the_public_news_folder(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, [
            'slug' => 'flood-image',
            'image_path' => 'images/news/banjir1.jpg',
        ]);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonPath('data.0.image_url', asset('images/news/banjir1.jpg'));
    }

    public function test_mobile_feed_uses_the_same_bnpb_and_bmkg_content_as_the_website(): void
    {
        $this->mock(NewsEducationService::class, function ($mock): void {
            $mock->shouldReceive('items')->twice()->andReturn([
                $this->feedItem('bnpb', 'news', 'Berita Terkini', 'Banjir terkini'),
                $this->feedItem('bmkg', 'education', 'Edukasi', 'Kenali gempa'),
            ]);
        });

        $this->getJson('/api/v1/mobile/news?category=berita')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source_type', 'bnpb')
            ->assertJsonPath('data.0.category', 'Berita Terkini');

        $this->getJson('/api/v1/mobile/news?category=edukasi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source_type', 'bmkg')
            ->assertJsonPath('data.0.category', 'Edukasi');
    }

    public function test_mobile_external_article_detail_returns_content_and_source_attribution(): void
    {
        $item = $this->feedItem('bnpb', 'news', 'Berita Terkini', 'Banjir terkini');
        $this->mock(NewsEducationService::class, function ($mock) use ($item): void {
            $mock->shouldReceive('findExternal')->once()->with('bnpb', $item['id'])->andReturn($item);
        });

        $this->getJson('/api/v1/mobile/news/bnpb/'.$item['id'])
            ->assertOk()
            ->assertJsonPath('data.content', $item['summary'])
            ->assertJsonPath('data.source', 'BNPB');
    }

    /** @return array<string, mixed> */
    private function feedItem(string $sourceType, string $categoryKey, string $category, string $title): array
    {
        $id = str_repeat('a', 64);

        return [
            'id' => $id,
            'source_type' => $sourceType,
            'category_key' => $categoryKey,
            'category' => $category,
            'title' => $title,
            'summary' => 'Ringkasan resmi.',
            'published_at' => now()->toIso8601String(),
            'source' => $sourceType === 'bnpb' ? 'BNPB' : 'BMKG',
            'source_url' => 'https://www.'.$sourceType.'.go.id/sumber',
            'image_url' => 'https://images.example.test/article.jpg',
            'content' => null,
        ];
    }

    public function test_public_feed_preserves_an_allowlisted_bmkg_shakemap_url(): void
    {
        $author = User::factory()->create();
        $this->createArticle($author, [
            'slug' => 'bmkg-shakemap',
            'image_path' => 'https://static.bmkg.go.id/20261024105749.mmi.jpg',
        ]);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonPath(
                'data.0.image_url',
                'https://static.bmkg.go.id/20261024105749.mmi.jpg',
            );
    }

    private function createArticle(User $author, array $overrides = []): News
    {
        return News::query()->create(array_merge([
            'author_id' => $author->id,
            'title' => 'Artikel Uji',
            'slug' => 'artikel-uji',
            'excerpt' => 'Ringkasan artikel uji.',
            'content' => '<p>Konten artikel uji.</p>',
            'category' => 'Berita',
            'source' => 'Sumber Uji',
            'source_url' => 'https://example.test/source',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }
}
