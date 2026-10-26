<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_about_and_help_are_public_and_have_working_named_routes(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Bersama')
            ->assertSee('hadapi bencana')
            ->assertSee('href="/login"', false)
            ->assertSee('href="/register"', false)
            ->assertSee('Masuk sebagai', false)
            ->assertSee('>Guest</span>', false)
            ->assertSee('href="'.route('disaster-alerts.index').'"', false)
            ->assertSee('href="'.route('about.index').'"', false)
            ->assertSee('href="'.route('help.index').'"', false);

        $this->get(route('about.index'))
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Tentang SIGAP BENCANA')
            ->assertSee('Lihat Bantuan')
            ->assertSee('Masuk ke SIGAP BENCANA');

        $this->get(route('help.index'))
            ->assertOk()
            ->assertSee('id="sidebar"', false)
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

        $this->get(route('about.index'))
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Tentang Sigap Bencana')
            ->assertSee('Bantuan');

        $this->get(route('help.index'))
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Pusat Bantuan')
            ->assertSee('Keluar')
            ->assertDontSee('href="/register"', false);
    }

    public function test_all_roles_receive_authenticated_about_and_help_pages_with_sidebar(): void
    {
        foreach (['masyarakat', 'admin', 'petugas_lapangan'] as $index => $role) {
            $user = new User;
            $user->forceFill([
                'id' => 987660 + $index,
                'name' => 'Pengguna '.ucfirst($role),
                'email' => $role.'-information-page@example.test',
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('about.index'))
                ->assertOk()
                ->assertSee('id="sidebar"', false)
                ->assertSee('Tentang Sigap Bencana');

            $this->get(route('help.index'))
                ->assertOk()
                ->assertSee('id="sidebar"', false)
                ->assertSee('Pusat Bantuan');
        }
    }

    public function test_help_page_contains_all_twenty_three_faqs_and_filter_controls(): void
    {
        $response = $this->get(route('help.index'))->assertOk();

        $this->assertSame(23, substr_count($response->getContent(), 'class="faq-item'));
        $response->assertSee('id="faq-search"', false)
            ->assertSee('id="faq-category"', false)
            ->assertSee('lg:grid-cols-3', false)
            ->assertSee('lg:col-span-2', false)
            ->assertSee('aria-label="Informasi tambahan"', false)
            ->assertSee('Tidak ada pertanyaan yang cocok.', false)
            ->assertSee('data-category="sos"', false)
            ->assertSee('data-category="evakuasi"', false);
    }

    public function test_private_community_pages_still_redirect_guests_to_login(): void
    {
        foreach ([
            route('home'),
            route('reports.create'),
            route('reports.index'),
            route('sos.create'),
            route('profile.edit'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_public_information_pages_are_available_to_guests(): void
    {
        $this->get(route('risk-map.index'))
            ->assertOk()
            ->assertSee('Peta Risiko')
            ->assertSee('Masuk');

        $this->get(route('emergency-contacts.index'))
            ->assertOk()
            ->assertSee('Kontak Darurat')
            ->assertSee('Masuk');
    }
}
