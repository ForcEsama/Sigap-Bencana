<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_landing_about_and_help_are_public_and_have_working_named_routes(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Bersama')
            ->assertSee('hadapi bencana')
            ->assertSee('href="/login"', false)
            ->assertSee('href="/register"', false);

        $this->get(route('about.index'))
            ->assertOk()
            ->assertSee('Tentang SIGAP BENCANA')
            ->assertSee('Lihat Bantuan')
            ->assertSee('Masuk ke SIGAP BENCANA');

        $this->get(route('help.index'))
            ->assertOk()
            ->assertSee('Pusat Bantuan')
            ->assertSee('Dalam keadaan darurat');

        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_public_pages_remain_available_when_user_is_authenticated(): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 987654,
            'name' => 'Pengunjung Terautentikasi',
            'email' => 'public-page@example.test',
            'role' => 'masyarakat',
        ]);

        $this->actingAs($user)->get(route('landing'))->assertOk();
        $this->get(route('about.index'))->assertOk();
        $this->get(route('help.index'))->assertOk();
    }

    public function test_help_page_contains_all_twenty_three_faqs_and_filter_controls(): void
    {
        $response = $this->get(route('help.index'))->assertOk();

        $this->assertSame(23, substr_count($response->getContent(), 'class="faq-item'));
        $response->assertSee('id="faq-search"', false)
            ->assertSee('id="faq-category"', false)
            ->assertSee('Tidak ada pertanyaan yang cocok.', false)
            ->assertSee('data-category="sos"', false)
            ->assertSee('data-category="evakuasi"', false);
    }

    public function test_private_community_pages_still_redirect_guests_to_login(): void
    {
        foreach ([
            route('home'),
            route('risk-map.index'),
            route('reports.create'),
            route('reports.index'),
            route('sos.create'),
            route('profile.edit'),
            route('emergency-contacts.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }
}
