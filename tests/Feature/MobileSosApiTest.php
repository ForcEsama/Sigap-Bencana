<?php

namespace Tests\Feature;

use App\Models\DisasterReport;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileSosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sos_request_is_created_once_for_a_client_request_id(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('sos-test')->plainTextToken;
        $payload = [
            'client_request_id' => (string) Str::uuid(),
            'disaster_type' => 'banjir',
            'description' => 'Air memasuki rumah.',
            'latitude' => -0.95,
            'longitude' => 100.35,
            'address' => 'Kota Padang',
        ];

        $created = $this->withToken($token)
            ->postJson('/api/v1/sos', $payload)
            ->assertCreated()
            ->assertJsonPath('data.disaster_type', 'banjir')
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonStructure(['data' => ['reference', 'events']]);

        $this->withToken($token)
            ->postJson('/api/v1/sos', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $created->json('data.id'));

        $this->assertSame(1, SosRequest::query()->where('user_id', $user->id)->count());
    }

    public function test_field_officer_cannot_create_a_public_sos_request(): void
    {
        $officer = User::factory()->create(['role' => 'petugas_lapangan']);
        $token = $officer->createToken('field-sos-create-test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/sos', [
                'client_request_id' => (string) Str::uuid(),
                'disaster_type' => 'banjir',
                'latitude' => -0.95,
                'longitude' => 100.35,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('sos_requests', 0);
    }

    public function test_sos_database_failure_returns_a_safe_retryable_response(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('sos-test')->plainTextToken;
        $payload = [
            'client_request_id' => (string) Str::uuid(),
            'disaster_type' => 'banjir',
            'latitude' => -0.95,
            'longitude' => 100.35,
        ];

        DB::statement("CREATE TRIGGER fail_sos_request_insert BEFORE INSERT ON sos_requests BEGIN SELECT RAISE(ABORT, 'simulated database failure'); END");

        $this->withToken($token)
            ->postJson('/api/v1/sos', $payload)
            ->assertServiceUnavailable()
            ->assertExactJson([
                'message' => 'Server belum dapat menyimpan permintaan SOS. Coba kirim ulang permintaan yang sama beberapa saat lagi.',
            ])
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('simulated database failure');

        $this->assertDatabaseCount('sos_requests', 0);
    }

    public function test_sos_history_and_detail_are_scoped_to_the_request_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $request = SosRequest::query()->create([
            'user_id' => $owner->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);
        $token = $otherUser->createToken('sos-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me/sos')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/me/sos/'.$request->id)
            ->assertNotFound();
    }

    public function test_sos_history_returns_requests_for_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $request = SosRequest::query()->create([
            'user_id' => $owner->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);
        $token = $owner->createToken('sos-owner-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me/sos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $request->id);
    }

    public function test_mobile_sos_history_no_longer_returns_a_request_deleted_from_mysql(): void
    {
        $owner = User::factory()->create();
        $request = SosRequest::query()->create([
            'user_id' => $owner->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);
        $request->histories()->create([
            'actor_id' => $owner->id,
            'new_status' => 'sent',
            'notes' => 'Permintaan dicatat.',
        ]);
        $token = $owner->createToken('sos-owner-test')->plainTextToken;

        DB::table('sos_requests')->where('id', $request->id)->delete();

        $this->withToken($token)
            ->getJson('/api/v1/me/sos')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseMissing('incident_histories', [
            'sos_request_id' => $request->id,
        ]);
    }

    public function test_field_task_feed_contains_only_reports_and_sos_assigned_to_that_field_officer(): void
    {
        $fieldOfficer = User::factory()->create(['role' => 'petugas_lapangan']);
        $otherOfficer = User::factory()->create(['role' => 'petugas_lapangan']);
        $admin = User::factory()->create(['role' => 'admin']);
        $reportOwner = User::factory()->create();
        $assignedReport = DisasterReport::query()->create([
            'user_id' => $reportOwner->id,
            'title' => 'Tugas yang ditugaskan',
            'disaster_type' => 'banjir',
            'description' => 'Laporan untuk petugas ini.',
            'address' => 'Padang',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'verification_status' => 'verified',
            'handling_status' => 'unhandled',
        ]);
        $assignedReport->assignment()->create([
            'assigned_to' => $fieldOfficer->id,
            'assigned_by' => $admin->id,
        ]);
        $otherReport = DisasterReport::query()->create([
            'user_id' => $reportOwner->id,
            'title' => 'Tugas petugas lain',
            'disaster_type' => 'gempa',
            'description' => 'Tidak boleh terlihat.',
            'address' => 'Padang',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'verification_status' => 'verified',
            'handling_status' => 'unhandled',
        ]);
        $otherReport->assignment()->create([
            'assigned_to' => $otherOfficer->id,
            'assigned_by' => $admin->id,
        ]);
        $assignedSos = SosRequest::query()->create([
            'user_id' => $reportOwner->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'assigned',
            'assigned_to' => $fieldOfficer->id,
            'requested_at' => now(),
        ]);

        $token = $fieldOfficer->createToken('field-task-test')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/field/tasks')
            ->assertOk()
            ->assertJsonPath('data.reports.0.id', $assignedReport->id)
            ->assertJsonCount(1, 'data.reports')
            ->assertJsonPath('data.sos.0.id', $assignedSos->id)
            ->assertJsonCount(1, 'data.sos')
            ->assertDontSee('Tugas petugas lain');

        $adminToken = User::factory()->create(['role' => 'admin'])
            ->createToken('admin-field-task-test')->plainTextToken;
        Auth::forgetGuards();
        $this->withToken($adminToken)->getJson('/api/v1/field/tasks')->assertForbidden();
    }

    public function test_admin_assigns_sos_and_only_assigned_field_officer_can_advance_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $assignedOfficer = User::factory()->create([
            'role' => 'petugas_lapangan',
            'is_active' => true,
        ]);
        $otherOfficer = User::factory()->create([
            'role' => 'petugas_lapangan',
            'is_active' => true,
        ]);
        $requester = User::factory()->create();
        $sos = SosRequest::query()->create([
            'user_id' => $requester->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);
        $adminToken = $admin->createToken('sos-assignment-admin')->plainTextToken;
        $assignedToken = $assignedOfficer->createToken('sos-assignment-field')->plainTextToken;
        $otherToken = $otherOfficer->createToken('sos-unassigned-field')->plainTextToken;

        $this->withToken($adminToken)
            ->patchJson('/api/v1/operations/sos/'.$sos->id.'/status', [
                'status' => 'assigned',
                'assigned_to' => $assignedOfficer->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.assigned_to', $assignedOfficer->id);

        Auth::forgetGuards();
        $this->withToken($otherToken)
            ->patchJson('/api/v1/operations/sos/'.$sos->id.'/status', [
                'status' => 'en_route',
            ])
            ->assertForbidden();

        Auth::forgetGuards();
        $this->withToken($assignedToken)
            ->patchJson('/api/v1/operations/sos/'.$sos->id.'/status', [
                'status' => 'en_route',
                'notes' => 'Petugas menuju lokasi.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'en_route');

        $this->assertDatabaseHas('sos_requests', [
            'id' => $sos->id,
            'assigned_to' => $assignedOfficer->id,
            'status' => 'en_route',
        ]);
    }
}
