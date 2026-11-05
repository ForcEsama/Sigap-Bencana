<?php

namespace Tests\Feature;

use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_only_active_emergency_contacts(): void
    {
        EmergencyContact::query()->create([
            'name' => 'Active Service',
            'category' => 'Emergency',
            'phone' => '112',
            'is_active' => true,
        ]);
        EmergencyContact::query()->create([
            'name' => 'Inactive Service',
            'category' => 'Emergency',
            'phone' => '111',
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/emergency-contacts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Active Service')
            ->assertJsonMissing(['name' => 'Inactive Service']);
    }

    public function test_authenticated_user_receives_only_active_contacts_in_web_order(): void
    {
        $user = User::factory()->create();
        EmergencyContact::query()->create([
            'name' => 'Zeta Service',
            'category' => 'Rescue',
            'phone' => '115',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        EmergencyContact::query()->create([
            'name' => 'Alpha Service',
            'category' => 'Emergency',
            'phone' => '112',
            'description' => 'Official service.',
            'region' => 'Padang',
            'address' => 'Padang, Indonesia',
            'website_url' => 'https://example.test',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        EmergencyContact::query()->create([
            'name' => 'Inactive Service',
            'category' => 'Emergency',
            'phone' => '111',
            'is_active' => false,
            'sort_order' => 0,
        ]);
        $token = $user->createToken('emergency-contact-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/emergency-contacts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Service')
            ->assertJsonPath('data.0.phone', '112')
            ->assertJsonPath('data.0.website_url', 'https://example.test')
            ->assertJsonPath('data.1.name', 'Zeta Service')
            ->assertJsonMissing(['name' => 'Inactive Service']);
    }

    public function test_guest_receives_an_empty_list_when_no_contacts_are_active(): void
    {
        EmergencyContact::query()->create([
            'name' => 'Inactive Service',
            'category' => 'Emergency',
            'phone' => '111',
            'is_active' => false,
        ]);
        $this->getJson('/api/v1/emergency-contacts')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
