<?php

namespace Tests\Feature;

use App\Models\EmergencyContact;
use App\Models\User;
use Database\Seeders\EmergencyContactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_update_extended_profile_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Alamat Domisili');

        $this->patch(route('profile.update'), [
            'form_type' => 'profile',
            'name' => 'Dina Warga',
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
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Dina Warga',
            'phone' => '081234567890',
            'domicile_address' => 'Jl. Merdeka 10',
            'province' => 'Sumatera Barat',
            'regency_city' => 'Kota Padang',
            'district' => 'Padang Barat',
            'village' => 'Belakang Tangsi',
            'postal_code' => '25111',
            'gender' => 'perempuan',
        ]);

        $this->assertSame('1999-04-12', $user->fresh()->date_of_birth->format('Y-m-d'));
    }

    public function test_user_can_change_password_after_current_password_check(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassword123')]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'form_type' => 'password',
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_basic_profile_can_be_updated_when_optional_database_columns_are_missing(): void
    {
        $user = User::factory()->create();
        Schema::shouldReceive('hasColumn')->andReturn(false);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'form_type' => 'profile',
                'name' => 'Nama Diperbarui',
                'email' => $user->email,
                'phone' => '081234567890',
                'domicile_address' => '',
                'province' => '',
                'regency_city' => '',
                'district' => '',
                'village' => '',
                'postal_code' => '',
                'date_of_birth' => '',
                'gender' => '',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Diperbarui',
            'phone' => '081234567890',
        ]);
    }

    public function test_emergency_contacts_are_seeded_and_visible_on_web(): void
    {
        $this->seed(EmergencyContactSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('emergency-contacts.index'))
            ->assertOk()
            ->assertSee('Padang Sigap')
            ->assertSee('BPBD Kota Padang');

        $this->assertSame(6, EmergencyContact::query()->count());
    }
}
