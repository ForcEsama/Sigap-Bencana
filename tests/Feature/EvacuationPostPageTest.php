<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvacuationPostPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_evacuation_posts(): void
    {
        $this->get(route('evacuation-posts.index'))
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Kontak Darurat')
            ->assertSee('Masuk');
    }

    public function test_authenticated_user_sees_same_planning_candidates_as_website_map(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('evacuation-posts.index'))
            ->assertOk()
            ->assertSee('TEA A1 - Lahan Kosong di Kelurahan Batipuh Panjang')
            ->assertSee('12 lokasi pada draf CSV')
            ->assertSee('data-focus-planning="A1"', false)
            ->assertSee('Semua belum diverifikasi dan tidak boleh digunakan sebagai panduan evakuasi.');
    }
}
