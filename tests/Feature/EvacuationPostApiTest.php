<?php

namespace Tests\Feature;

use App\Models\EvacuationPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvacuationPostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_evacuation_posts_and_planning_candidates(): void
    {
        $this->getJson('/api/v1/evacuation-posts')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonCount(12, 'planning_candidates');
    }

    public function test_authenticated_user_receives_evacuation_posts_with_map_and_detail_fields(): void
    {
        $user = User::factory()->create();
        $post = EvacuationPost::query()->create([
            'name' => 'Posko A',
            'description' => 'Gedung serbaguna.',
            'address' => 'Padang Utara',
            'latitude' => -0.896402,
            'longitude' => 100.355539,
            'capacity' => 120,
            'current_occupancy' => 25,
            'facilities' => 'Toilet, air bersih',
            'contact_person' => 'Petugas Posko',
            'contact_phone' => '0751000000',
            'status' => 'available',
        ]);
        $token = $user->createToken('evacuation-post-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/evacuation-posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.name', 'Posko A')
            ->assertJsonPath('data.0.latitude', -0.896402)
            ->assertJsonPath('data.0.longitude', 100.355539)
            ->assertJsonPath('data.0.has_valid_coordinates', true)
            ->assertJsonPath('data.0.capacity', 120)
            ->assertJsonPath('data.0.current_occupancy', 25)
            ->assertJsonPath('data.0.status', 'available');
    }

    public function test_guest_receives_planning_candidates_when_database_has_no_places(): void
    {
        $this->getJson('/api/v1/evacuation-posts')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonCount(12, 'planning_candidates')
            ->assertJsonPath('planning_candidates.0.location_code', 'A1')
            ->assertJsonPath('planning_candidates.0.recommended_capacity', '22919')
            ->assertJsonPath('planning_candidates.0.image_path', 'images/evacuation/tea-a1.jpg')
            ->assertJsonPath('planning_candidates.0.is_planning_candidate', true)
            ->assertJsonPath('planning_candidates.0.has_verified_coordinates', false)
            ->assertJsonPath('planning_candidates.0.has_candidate_coordinates', true);
    }
}
