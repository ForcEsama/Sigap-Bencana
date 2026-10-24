<?php

namespace Tests\Feature;

use App\Models\DisasterReport;
use App\Models\IncidentHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisasterReportApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('disaster-report-api-test')->plainTextToken;
    }

    private function reportFor(User $owner, array $attributes = []): DisasterReport
    {
        return DisasterReport::query()->create([
            'user_id' => $owner->id,
            'title' => 'Laporan uji',
            'disaster_type' => 'banjir',
            'description' => 'Deskripsi rahasia milik pelapor.',
            'address' => 'Alamat privat pelapor',
            'latitude' => '-6.2000000',
            'longitude' => '106.8166660',
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
            ...$attributes,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return [
            'title' => 'Laporan API dari pengguna',
            'disaster_type' => 'banjir',
            'description' => 'Air mulai memasuki beberapa rumah.',
            'address' => 'Jalan Merdeka, Jakarta',
            'latitude' => '-6.2000000',
            'longitude' => '106.8166660',
            'occurred_at' => null,
            ...$overrides,
        ];
    }

    public function test_guest_receives_json_unauthorized_for_report_read_and_create_routes(): void
    {
        $report = $this->reportFor(User::factory()->create());

        $this->getJson('/api/reports')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->getJson('/api/reports/'.$report->id)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->postJson('/api/reports', $this->validPayload())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->assertDatabaseCount('disaster_reports', 1);
    }

    public function test_owner_can_view_their_report_detail(): void
    {
        $owner = User::factory()->create();
        $report = $this->reportFor($owner, [
            'title' => 'Laporan milik saya',
            'description' => 'Deskripsi privat yang hanya milik pemilik.',
        ]);

        $this->withToken($this->tokenFor($owner))
            ->getJson('/api/reports/'.$report->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'Laporan milik saya')
            ->assertJsonPath('data.description', 'Deskripsi privat yang hanya milik pemilik.');
    }

    public function test_owner_cannot_view_another_users_report_or_its_contents(): void
    {
        $owner = User::factory()->create();
        $otherUserReport = $this->reportFor(User::factory()->create(), [
            'title' => 'JUDUL RAHASIA PELAPOR LAIN',
            'description' => 'ISI RAHASIA PELAPOR LAIN',
            'address' => 'ALAMAT RAHASIA PELAPOR LAIN',
        ]);

        $response = $this->withToken($this->tokenFor($owner))
            ->getJson('/api/reports/'.$otherUserReport->id)
            ->assertNotFound();

        $response->assertDontSee('JUDUL RAHASIA PELAPOR LAIN')
            ->assertDontSee('ISI RAHASIA PELAPOR LAIN')
            ->assertDontSee('ALAMAT RAHASIA PELAPOR LAIN');
    }

    public function test_report_list_contains_only_the_authenticated_users_reports(): void
    {
        $owner = User::factory()->create();
        $this->reportFor($owner, ['title' => 'Laporan pemilik']);
        $this->reportFor(User::factory()->create(), [
            'title' => 'JUDUL MILIK PENGGUNA LAIN',
            'description' => 'ISI MILIK PENGGUNA LAIN',
        ]);

        $this->withToken($this->tokenFor($owner))
            ->getJson('/api/reports')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laporan pemilik')
            ->assertJsonMissing(['title' => 'JUDUL MILIK PENGGUNA LAIN'])
            ->assertDontSee('ISI MILIK PENGGUNA LAIN');
    }

    public function test_report_creation_ignores_forged_owner_and_status_metadata(): void
    {
        $owner = User::factory()->create();
        $forgedUser = User::factory()->create();

        $response = $this->withToken($this->tokenFor($owner))
            ->postJson('/api/reports', $this->validPayload([
                'user_id' => $forgedUser->id,
                'verification_status' => 'verified',
                'handling_status' => 'resolved',
                'verified_by' => $forgedUser->id,
                'verified_at' => now()->toDateTimeString(),
            ]))
            ->assertCreated()
            ->assertJsonPath('data.user_id', $owner->id)
            ->assertJsonPath('data.verification_status', 'pending')
            ->assertJsonPath('data.handling_status', 'unhandled')
            ->assertJsonPath('data.verified_by', null)
            ->assertJsonPath('data.verified_at', null);

        $this->assertDatabaseCount('disaster_reports', 1);
        $this->assertDatabaseHas('disaster_reports', [
            'id' => $response->json('data.id'),
            'user_id' => $owner->id,
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function test_report_creation_accepts_the_client_supplied_title(): void
    {
        $owner = User::factory()->create();

        $this->withToken($this->tokenFor($owner))
            ->postJson('/api/reports', $this->validPayload(['title' => 'Judul yang dikirim klien']))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Judul yang dikirim klien');

        $this->assertDatabaseHas('disaster_reports', [
            'user_id' => $owner->id,
            'title' => 'Judul yang dikirim klien',
        ]);
    }

    public function test_api_report_responses_include_raw_internal_report_columns(): void
    {
        $owner = User::factory()->create();
        $officer = User::factory()->create(['role' => 'petugas']);
        $report = $this->reportFor($owner, [
            'photo_path' => 'disaster-reports/private-photo.jpg',
            'verification_status' => 'verified',
            'verified_by' => $officer->id,
            'verified_at' => now(),
        ]);
        $token = $this->tokenFor($owner);

        $this->withToken($token)
            ->getJson('/api/reports')
            ->assertOk()
            ->assertJsonPath('data.0.photo_path', 'disaster-reports/private-photo.jpg')
            ->assertJsonPath('data.0.verified_by', $officer->id)
            ->assertJsonPath('data.0.verified_at', $report->fresh()->verified_at->toJSON());

        $this->withToken($token)
            ->getJson('/api/reports/'.$report->id)
            ->assertOk()
            ->assertJsonPath('data.photo_path', 'disaster-reports/private-photo.jpg')
            ->assertJsonPath('data.verified_by', $officer->id);

        $createdResponse = $this->withToken($token)
            ->postJson('/api/reports', $this->validPayload())
            ->assertCreated();

        $createdData = $createdResponse->json('data');
        $this->assertArrayNotHasKey('photo_path', $createdData);
        $this->assertArrayNotHasKey('verified_by', $createdData);
        $this->assertArrayNotHasKey('verified_at', $createdData);
    }

    public function test_regular_user_cannot_verify_or_change_report_handling(): void
    {
        $user = User::factory()->create(['role' => 'masyarakat']);
        $report = $this->reportFor($user);

        $this->withToken($this->tokenFor($user))
            ->patchJson('/api/reports/'.$report->id.'/verification', ['decision' => 'verified'])
            ->assertForbidden();

        $this->withToken($this->tokenFor($user))
            ->patchJson('/api/reports/'.$report->id.'/handling', ['status' => 'in_progress'])
            ->assertForbidden();

        $this->assertDatabaseHas('disaster_reports', [
            'id' => $report->id,
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
        ]);
        $this->assertDatabaseCount('incident_histories', 0);
    }

    public function test_officer_can_verify_pending_report_and_update_handling_after_verification(): void
    {
        $officer = User::factory()->create(['role' => 'petugas', 'is_active' => true]);
        $report = $this->reportFor(User::factory()->create());
        $token = $this->tokenFor($officer);

        $this->withToken($token)
            ->patchJson('/api/reports/'.$report->id.'/verification', [
                'decision' => 'verified',
                'notes' => 'Data laporan diperiksa.',
            ])
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'verified')
            ->assertJsonPath('data.verified_by', $officer->id);

        $this->withToken($token)
            ->patchJson('/api/reports/'.$report->id.'/handling', [
                'status' => 'in_progress',
                'notes' => 'Penanganan dimulai.',
            ])
            ->assertOk()
            ->assertJsonPath('data.handling_status', 'in_progress');

        $this->assertDatabaseHas('disaster_reports', [
            'id' => $report->id,
            'verification_status' => 'verified',
            'handling_status' => 'in_progress',
            'verified_by' => $officer->id,
        ]);
        $this->assertSame(2, IncidentHistory::query()->where('disaster_report_id', $report->id)->count());
        $this->assertDatabaseHas('incident_histories', [
            'disaster_report_id' => $report->id,
            'actor_id' => $officer->id,
            'old_status' => 'pending',
            'new_status' => 'verified',
        ]);
    }

    public function test_handling_requires_verified_report_and_verification_cannot_be_repeated(): void
    {
        $officer = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $report = $this->reportFor(User::factory()->create());
        $token = $this->tokenFor($officer);

        $this->withToken($token)
            ->patchJson('/api/reports/'.$report->id.'/handling', ['status' => 'resolved'])
            ->assertStatus(409);

        $this->withToken($token)
            ->patchJson('/api/reports/'.$report->id.'/verification', ['decision' => 'rejected'])
            ->assertOk();

        $this->withToken($token)
            ->patchJson('/api/reports/'.$report->id.'/verification', ['decision' => 'verified'])
            ->assertStatus(409);

        $this->assertDatabaseHas('disaster_reports', [
            'id' => $report->id,
            'verification_status' => 'rejected',
            'handling_status' => 'unhandled',
        ]);
    }

    public function test_officer_cannot_submit_unknown_verification_or_handling_statuses(): void
    {
        $officer = User::factory()->create(['role' => 'petugas', 'is_active' => true]);
        $report = $this->reportFor(User::factory()->create());

        $this->withToken($this->tokenFor($officer))
            ->patchJson('/api/reports/'.$report->id.'/verification', ['decision' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('decision');

        $this->withToken($this->tokenFor($officer))
            ->patchJson('/api/reports/'.$report->id.'/handling', ['status' => 'complete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('disaster_reports', [
            'id' => $report->id,
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
        ]);
    }

    public function test_inactive_officer_cannot_change_report_status(): void
    {
        $officer = User::factory()->create(['role' => 'petugas', 'is_active' => false]);
        $report = $this->reportFor(User::factory()->create());

        $this->withToken($this->tokenFor($officer))
            ->patchJson('/api/reports/'.$report->id.'/verification', ['decision' => 'verified'])
            ->assertForbidden();

        $this->assertDatabaseHas('disaster_reports', [
            'id' => $report->id,
            'verification_status' => 'pending',
        ]);
    }
}
