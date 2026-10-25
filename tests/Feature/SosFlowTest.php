<?php

namespace Tests\Feature;

use App\Models\IncidentHistory;
use App\Models\SosRequest;
use App\Models\User;
use App\Services\UserNotificationWriter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SosFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_sos_pages_and_submission(): void
    {
        $this->get(route('sos.create'))->assertRedirect(route('login'));
        $this->get(route('sos.confirm'))->assertRedirect(route('login'));
        $this->post(route('sos.store'), [])->assertRedirect(route('login'));

        $this->assertDatabaseCount('sos_requests', 0);
    }

    public function test_user_can_select_emergency_type_and_open_location_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('sos.create'))
            ->assertOk()
            ->assertSee('Pilih Jenis Keadaan Darurat')
            ->assertSee('Gempa Bumi')
            ->assertSee('Tsunami')
            ->assertSee('belum terhubung')
            ->assertDontSee('Mengirim Permintaan Bantuan...');

        $this->get(route('sos.confirm'))->assertRedirect(route('sos.create'));

        $this->post(route('sos.confirm-selection'), ['disaster_type' => 'gempa'])
            ->assertRedirect(route('sos.confirm'));

        $this->get(route('sos.confirm'))
            ->assertOk()
            ->assertSee('Konfirmasi Lokasi')
            ->assertSee('Gempa Bumi')
            ->assertSee('Pilih titik pada peta')
            ->assertSee('id="sos-loading-screen" class="hidden', false)
            ->assertSee('Mengirim Permintaan Bantuan...');
    }

    public function test_invalid_emergency_type_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('sos.create'))
            ->post(route('sos.confirm-selection'), ['disaster_type' => 'unknown'])
            ->assertRedirect(route('sos.create'))
            ->assertSessionHasErrors('disaster_type');

        $this->assertDatabaseCount('sos_requests', 0);
    }

    public function test_request_is_saved_with_server_owned_status_and_initial_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post(route('sos.confirm-selection'), ['disaster_type' => 'banjir'])->assertRedirect(route('sos.confirm'));
        $this->get(route('sos.confirm'))->assertOk();
        $key = session('sos.submission_key');

        $this->post(route('sos.store'), [
            'submission_key' => $key,
            'latitude' => '-0.9333000',
            'longitude' => '100.3250000',
            'address' => '  Lokasi yang dimasukkan pengguna  ',
            'user_id' => User::factory()->create()->id,
            'status' => 'completed',
            'assigned_to' => User::factory()->create()->id,
        ])->assertRedirect(route('sos.sent', 1));

        $this->assertDatabaseCount('sos_requests', 1);
        $request = SosRequest::query()->firstOrFail();
        $this->assertSame($user->id, $request->user_id);
        $this->assertSame('banjir', $request->disaster_type);
        $this->assertSame('sent', $request->status);
        $this->assertNull($request->assigned_to);
        $this->assertSame('Lokasi yang dimasukkan pengguna', $request->address);
        $this->assertDatabaseHas('incident_histories', [
            'sos_request_id' => $request->id,
            'actor_id' => $user->id,
            'old_status' => null,
            'new_status' => 'sent',
        ]);

        $this->get(route('sos.sent', $request->id))
            ->assertOk()
            ->assertSee('SOS-000001')
            ->assertSee('tersimpan di aplikasi')
            ->assertSee('belum terhubung');

        $this->get(route('sos.status', $request->id))
            ->assertOk()
            ->assertSee('Permintaan Disimpan')
            ->assertSee('Belum ada konfirmasi')
            ->assertSee('tidak meneruskan permintaan');
    }

    public function test_invalid_coordinates_are_rejected_and_no_request_is_created(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('sos.confirm-selection'), ['disaster_type' => 'tsunami']);
        $this->get(route('sos.confirm'));

        $this->from(route('sos.confirm'))
            ->post(route('sos.store'), [
                'submission_key' => session('sos.submission_key'),
                'latitude' => '90.01',
                'longitude' => '200',
            ])
            ->assertRedirect(route('sos.confirm'))
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertDatabaseCount('sos_requests', 0);
    }

    public function test_duplicate_submission_with_same_session_key_creates_only_one_request(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('sos.confirm-selection'), ['disaster_type' => 'longsor']);
        $this->get(route('sos.confirm'));

        $payload = [
            'submission_key' => session('sos.submission_key'),
            'latitude' => '-0.9',
            'longitude' => '100.3',
        ];

        $this->post(route('sos.store'), $payload)->assertRedirect(route('sos.sent', 1));
        $this->post(route('sos.store'), $payload)->assertRedirect(route('sos.sent', 1));

        $this->assertDatabaseCount('sos_requests', 1);
    }

    public function test_ajax_submission_returns_the_saved_request_destination_and_is_idempotent(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('sos.confirm-selection'), ['disaster_type' => 'banjir']);
        $this->get(route('sos.confirm'));

        $payload = [
            'submission_key' => session('sos.submission_key'),
            'latitude' => '-0.9333',
            'longitude' => '100.325',
        ];

        $firstResponse = $this->postJson(route('sos.store'), $payload)
            ->assertOk()
            ->assertJsonPath('redirect_url', route('sos.sent', 1));

        $this->postJson(route('sos.store'), $payload)
            ->assertOk()
            ->assertExactJson($firstResponse->json());

        $this->assertDatabaseCount('sos_requests', 1);
        $this->assertDatabaseCount('incident_histories', 1);
    }

    public function test_ajax_submission_failure_returns_an_error_without_creating_a_request(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('sos.confirm-selection'), ['disaster_type' => 'tsunami']);
        $this->get(route('sos.confirm'));

        $this->mock(UserNotificationWriter::class, function ($mock): void {
            $mock->shouldReceive('create')->once()->andThrow(new \RuntimeException('notification failure'));
        });

        $this->postJson(route('sos.store'), [
            'submission_key' => session('sos.submission_key'),
            'latitude' => '-0.9333',
            'longitude' => '100.325',
        ])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Permintaan belum tersimpan. Periksa koneksi lalu coba kirim kembali.');

        $this->assertDatabaseCount('sos_requests', 0);
        $this->assertDatabaseCount('incident_histories', 0);
    }

    public function test_user_cannot_view_another_users_request_or_status(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $request = $owner->sosRequests()->create([
            'disaster_type' => 'gempa',
            'latitude' => '-0.9',
            'longitude' => '100.3',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('sos.sent', $request->id))
            ->assertNotFound();

        $this->get(route('sos.status', $request->id))->assertNotFound();

        $this->assertSame(0, IncidentHistory::query()->count());
    }

    public function test_status_page_handles_a_request_without_history_without_inventing_progress(): void
    {
        $user = User::factory()->create();
        $request = $user->sosRequests()->create([
            'disaster_type' => 'gempa',
            'latitude' => '-0.9',
            'longitude' => '100.3',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('sos.status', $request->id))
            ->assertOk()
            ->assertSee('Permintaan Disimpan')
            ->assertSee('Konfirmasi Petugas')
            ->assertSee('Belum ada konfirmasi')
            ->assertSee('Belum ada informasi');

        $this->assertDatabaseCount('incident_histories', 0);
    }

    public function test_saved_request_time_is_converted_once_to_wib_on_confirmation_and_status_pages(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-25 11:52:14', 'UTC'));

        try {
            $user = User::factory()->create();
            $this->actingAs($user);
            $this->post(route('sos.confirm-selection'), ['disaster_type' => 'gempa']);
            $this->get(route('sos.confirm'));

            $this->post(route('sos.store'), [
                'submission_key' => session('sos.submission_key'),
                'latitude' => '-0.9333000',
                'longitude' => '100.3250000',
            ])->assertRedirect(route('sos.sent', 1));

            $sosRequest = SosRequest::query()->findOrFail(1);
            $this->assertSame('2026-10-25 11:52:14', $sosRequest->created_at->format('Y-m-d H:i:s'));
            $this->assertSame('18:52', $sosRequest->created_at->copy()->timezone(config('app.display_timezone'))->format('H:i'));

            $this->get(route('sos.sent', $sosRequest->id))
                ->assertOk()
                ->assertSee('Waktu disimpan')
                ->assertSee('25 Oktober 2026, 18:52 WIB');

            $statusResponse = $this->get(route('sos.status', $sosRequest->id))
                ->assertOk()
                ->assertSee('25 Oktober 2026, 18:52 WIB');
            $this->assertSame(2, substr_count($statusResponse->getContent(), '25 Oktober 2026, 18:52 WIB'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
