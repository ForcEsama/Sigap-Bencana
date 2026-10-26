<?php

namespace Tests\Feature;

use App\Models\RiskZone;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RiskMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_the_basemap_and_official_inarisk_layer(): void
    {
        $this->get(route('risk-map.index'))
            ->assertOk()
            ->assertSee('OpenStreetMap')
            ->assertSee('Legenda resmi')
            ->assertDontSee('Zona risiko lokal SIGAP BENCANA');
    }

    public function test_authenticated_user_sees_only_the_official_inarisk_map(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('risk-map.index'))
            ->assertOk()
            ->assertSee('Peta Risiko')
            ->assertSee('Legenda resmi')
            ->assertDontSee('Zona risiko lokal SIGAP BENCANA');
    }

    public function test_incomplete_risk_zone_schema_does_not_break_the_empty_state(): void
    {
        Schema::table('risk_zones', function (Blueprint $table) {
            $table->dropIndex('risk_zones_disaster_type_risk_level_index');
            $table->dropIndex('risk_zones_is_active_index');
            $table->dropColumn([
                'name',
                'disaster_type',
                'risk_level',
                'description',
                'region_name',
                'latitude',
                'longitude',
                'radius_meters',
                'source',
                'data_date',
                'is_active',
            ]);
        });

        $this->actingAs(User::factory()->create())
            ->get(route('risk-map.index'))
            ->assertOk()
            ->assertSee('Legenda resmi')
            ->assertDontSee('Data zona risiko lokal belum tersedia');
    }

    public function test_local_risk_zones_are_not_drawn_over_the_official_map(): void
    {
        $user = User::factory()->create();

        RiskZone::query()->create([
            'name' => 'Zona Risiko Uji',
            'disaster_type' => 'banjir',
            'risk_level' => 'tinggi',
            'region_name' => 'Kecamatan Uji',
            'latitude' => '-0.5000000',
            'longitude' => '100.5000000',
            'radius_meters' => 1200,
            'source' => 'Sumber Uji',
            'data_date' => '2026-10-24',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('risk-map.index'))
            ->assertOk()
            ->assertDontSee('Zona Risiko Uji')
            ->assertDontSee('Kecamatan Uji')
            ->assertDontSee('Sumber Uji')
            ->assertDontSee('Zona risiko lokal SIGAP BENCANA');
    }
}
