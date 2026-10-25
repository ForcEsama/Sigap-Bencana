<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_a_profile(): void
    {
        $this->patchJson('/api/user', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'phone' => '08123456789',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_supported_profile_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'lama@example.test',
            'email_verified_at' => now(),
            'phone' => '08111111111',
        ]);
        $token = $user->createToken('profile-test')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/user', [
                'name' => 'Nama Baru',
                'email' => 'baru@example.test',
                'phone' => '08222222222',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nama Baru')
            ->assertJsonPath('user.email', 'baru@example.test')
            ->assertJsonPath('user.phone', '08222222222');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'phone' => '08222222222',
            'email_verified_at' => null,
        ]);
    }

    public function test_profile_update_rejects_an_email_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.test']);
        $token = $user->createToken('profile-test')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/user', [
                'name' => 'Tidak Tersimpan',
                'email' => 'taken@example.test',
                'phone' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
