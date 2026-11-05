<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_links_exist_and_target_available_pages_for_every_role(): void
    {
        foreach (['masyarakat', 'admin', 'petugas_lapangan'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $about = $this->actingAs($user)
                ->get(route('about.index'))
                ->assertOk()
                ->assertSee('href="'.route('help.index').'"', false);

            $help = $this->get(route('help.index'))
                ->assertOk()
                ->assertSee('href="'.route('about.index').'"', false)
                ->assertSee('href="'.route('help.index').'"', false);

            foreach ([$about, $help] as $response) {
                $response->assertSee('href="'.route('risk-map.index').'"', false)
                    ->assertSee('href="'.route('evacuation-posts.index').'"', false)
                    ->assertSee('href="'.route('emergency-contacts.index').'"', false)
                    ->assertSee('href="'.route('profile.edit').'"', false);
            }

            foreach ([
                'risk-map.index',
                'evacuation-posts.index',
                'emergency-contacts.index',
                'profile.edit',
            ] as $routeName) {
                $this->get(route($routeName))->assertOk();
            }

            $this->get(route('reports.index'))
                ->assertOk()
                ->assertSee('href="'.route('help.index').'"', false);
        }
    }
}
