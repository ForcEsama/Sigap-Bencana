<?php

namespace Tests\Feature;

use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
