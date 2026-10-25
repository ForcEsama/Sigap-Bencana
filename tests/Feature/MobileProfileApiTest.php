<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_user_can_update_extended_profile_fields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('profile-test')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/user', [
                'name' => 'Nama Profil',
                'email' => $user->email,
                'phone' => '081234567890',
                'domicile_address' => 'Jl. Merdeka 10',
                'province' => 'Sumatera Barat',
                'regency_city' => 'Kota Padang',
                'district' => 'Padang Barat',
                'village' => 'Belakang Tangsi',
                'postal_code' => '25111',
                'date_of_birth' => '1999-04-12',
                'gender' => 'perempuan',
            ])
            ->assertOk()
            ->assertJsonPath('user.domicile_address', 'Jl. Merdeka 10')
            ->assertJsonPath('user.province', 'Sumatera Barat')
            ->assertJsonPath('user.regency_city', 'Kota Padang')
            ->assertJsonPath('user.district', 'Padang Barat')
            ->assertJsonPath('user.village', 'Belakang Tangsi')
            ->assertJsonPath('user.postal_code', '25111')
            ->assertJsonPath('user.date_of_birth', '1999-04-12T00:00:00.000000Z')
            ->assertJsonPath('user.gender', 'perempuan');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'domicile_address' => 'Jl. Merdeka 10',
            'province' => 'Sumatera Barat',
            'regency_city' => 'Kota Padang',
            'district' => 'Padang Barat',
            'village' => 'Belakang Tangsi',
            'postal_code' => '25111',
            'gender' => 'perempuan',
        ]);
    }

    public function test_extended_profile_update_returns_service_unavailable_when_columns_are_missing(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('profile-test')->plainTextToken;
        Schema::shouldReceive('hasColumn')->andReturn(false);

        $this->withToken($token)
            ->patchJson('/api/user', [
                'name' => 'Nama Belum Tersimpan',
                'email' => $user->email,
                'phone' => null,
                'domicile_address' => 'Alamat uji',
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Penyimpanan profil tambahan belum tersedia. Migrasi database belum siap.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
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
