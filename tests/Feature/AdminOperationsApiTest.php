<?php

namespace Tests\Feature;

use App\Models\DisasterReport;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminOperationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_queue_returns_operational_items_and_active_field_officers(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $reporter = User::factory()->create();
        $fieldOfficer = User::factory()->create([
            'name' => 'Petugas Aktif',
            'role' => 'petugas_lapangan',
            'is_active' => true,
        ]);
        User::factory()->create([
            'role' => 'petugas_lapangan',
            'is_active' => false,
        ]);
        $report = DisasterReport::query()->create([
            'user_id' => $reporter->id,
            'title' => 'Laporan untuk antrean admin',
            'disaster_type' => 'banjir',
            'description' => 'Laporan uji.',
            'address' => 'Padang',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
        ]);
        $sos = SosRequest::query()->create([
            'user_id' => $reporter->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);
        $token = $admin->createToken('admin-queue-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/operations/queue')
            ->assertOk()
            ->assertJsonPath('data.reports.0.id', $report->id)
            ->assertJsonPath('data.sos.0.id', $sos->id)
            ->assertJsonPath('data.field_officers.0.id', $fieldOfficer->id)
            ->assertJsonPath('data.field_officers.0.name', 'Petugas Aktif')
            ->assertJsonCount(1, 'data.field_officers');
    }

    public function test_non_admin_roles_cannot_read_the_admin_queue(): void
    {
        foreach (['masyarakat', 'petugas_lapangan'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $token = $user->createToken('non-admin-queue-test')->plainTextToken;

            Auth::forgetGuards();
            $this->withToken($token)
                ->getJson('/api/v1/operations/queue')
                ->assertForbidden();
        }
    }
}
