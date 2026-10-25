<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskMapLayerFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_all_configured_bnpb_risk_filter_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('risk-map.index'));

        $response->assertOk()
            ->assertSee('id="risk-type-filter"', false)
            ->assertSee('Peta Risiko Banjir')
            ->assertSee('Banjir Bandang')
            ->assertSee('Gempa Bumi')
            ->assertSee('Tsunami')
            ->assertSee('Tanah Longsor')
            ->assertSee('Letusan Gunung Api')
            ->assertSee('Kekeringan')
            ->assertSee('Cuaca Ekstrem')
            ->assertSee('Kebakaran Hutan dan Lahan')
            ->assertSee('Likuefaksi')
            ->assertSee('Gelombang Ekstrem dan Abrasi')
            ->assertSee('Multi-Bahaya')
            ->assertSee('InaRISK/BNPB')
            ->assertSee('Tanggal data tidak dicantumkan pada metadata REST yang diverifikasi.')
            ->assertSee('Layer peta bahaya, kerentanan, dan kejadian terkini tidak dicampur pada filter ini.');
    }
}
