<?php

namespace Tests\Feature;

use App\Models\SosRequest;
use App\Models\User;
use App\Services\BmkgEarthquakeService;
use App\Services\BmkgNowcastService;
use App\Services\BnpbNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryPath;

    protected function setUp(): void
    {
        parent::setUp();
        config(['logging.default' => 'null']);
        $this->temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sigap-notifications-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->temporaryPath.DIRECTORY_SEPARATOR.'views');
        config(['view.compiled' => $this->temporaryPath.DIRECTORY_SEPARATOR.'views']);
        $this->mockExternalFeeds();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryPath);
        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_notification_page_shows_empty_state_without_fabricated_items(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifikasi')
            ->assertSee('Belum ada notifikasi pada kategori ini.')
            ->assertDontSee('3 belum dibaca');
    }

    public function test_list_and_unread_count_are_scoped_to_the_signed_in_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->notifications()->create(['title' => 'Aktivitas milik saya', 'message' => 'Teks akun sendiri.', 'type' => 'activity']);
        $other->notifications()->create(['title' => 'Isi rahasia akun lain', 'message' => 'Jangan tampilkan ini.', 'type' => 'activity']);

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Aktivitas milik saya')
            ->assertSee('1 belum dibaca')
            ->assertDontSee('Isi rahasia akun lain')
            ->assertDontSee('Jangan tampilkan ini.');
    }

    public function test_filter_displays_only_the_selected_category(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create(['title' => 'Aktivitas saja', 'message' => 'Aktivitas.', 'type' => 'activity']);
        $user->notifications()->create(['title' => 'Informasi saja', 'message' => 'Informasi.', 'type' => 'information']);

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'information']))
            ->assertOk()
            ->assertSee('Informasi saja')
            ->assertDontSee('Aktivitas saja');
    }

    public function test_verified_external_sources_feed_warning_and_information_items_without_read_badges(): void
    {
        $fetchedAt = now()->toIso8601String();
        $this->instance(BmkgNowcastService::class, \Mockery::mock(BmkgNowcastService::class, function ($mock) use ($fetchedAt): void {
            $mock->shouldReceive('warnings')->andReturn([
                'status' => 'available', 'fetched_at' => $fetchedAt,
                'items' => [[
                    'title' => 'Peringatan Cuaca Aktif BMKG', 'summary' => 'Hujan lebat berpotensi terjadi.',
                    'region' => 'Wilayah yang tercantum sumber', 'source_url' => 'https://www.bmkg.go.id/alerts/nowcast/id/tes_alert.xml',
                    'published_at' => $fetchedAt, 'expires' => now()->addHour()->toIso8601String(),
                ]],
            ]);
        }));
        $this->instance(BmkgEarthquakeService::class, \Mockery::mock(BmkgEarthquakeService::class, function ($mock) use ($fetchedAt): void {
            $mock->shouldReceive('earthquakes')->andReturn([
                'status' => 'available', 'fetched_at' => $fetchedAt,
                'items' => [[
                    'event_key' => 'event-1', 'title' => 'Gempa M 5.1', 'region' => 'Wilayah sumber',
                    'summary' => 'Gempa tercatat oleh BMKG.', 'occurred_at' => $fetchedAt,
                    'source_url' => 'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json',
                ]],
            ]);
        }));
        $this->instance(BnpbNewsService::class, \Mockery::mock(BnpbNewsService::class, function ($mock) use ($fetchedAt): void {
            $mock->shouldReceive('news')->andReturn([
                'status' => 'available', 'fetched_at' => $fetchedAt,
                'items' => [[
                    'title' => 'Informasi BNPB', 'summary' => 'Informasi dari sumber BNPB.',
                    'published_at' => $fetchedAt, 'source_url' => 'https://www.bnpb.go.id/berita/informasi-uji',
                ]],
            ]);
        }));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'warning']))
            ->assertOk()
            ->assertSee('Peringatan Cuaca Aktif BMKG')
            ->assertSee('https://www.bmkg.go.id/alerts/nowcast/id/tes_alert.xml')
            ->assertDontSee('Gempa M 5.1');

        $this->get(route('notifications.index', ['filter' => 'information']))
            ->assertOk()
            ->assertSee('Gempa M 5.1')
            ->assertSee('Informasi BNPB')
            ->assertDontSee('Peringatan Cuaca Aktif BMKG');

        $this->assertSame(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_user_can_mark_only_their_own_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownedNotification = $owner->notifications()->create(['title' => 'Milik saya', 'message' => 'Isi.', 'type' => 'activity']);
        $otherNotification = $other->notifications()->create(['title' => 'Milik orang lain', 'message' => 'Rahasia.', 'type' => 'activity']);

        $this->actingAs($owner)
            ->patch(route('notifications.read', $ownedNotification->id), ['filter' => 'activity'])
            ->assertRedirect(route('notifications.index', ['filter' => 'activity']));
        $this->assertNotNull($ownedNotification->fresh()->read_at);

        $this->patch(route('notifications.read', $otherNotification->id))->assertNotFound();
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_mark_all_read_updates_only_the_signed_in_users_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownedNotification = $owner->notifications()->create(['title' => 'Milik saya', 'message' => 'Isi.', 'type' => 'activity']);
        $otherNotification = $other->notifications()->create(['title' => 'Milik orang lain', 'message' => 'Isi.', 'type' => 'activity']);

        $this->actingAs($owner)->patch(route('notifications.read-all'))->assertRedirect(route('notifications.index', ['filter' => 'all']));

        $this->assertNotNull($ownedNotification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_report_creation_persists_an_activity_notification_for_its_owner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('reports.store'), [
                'disaster_type' => 'banjir',
                'address' => 'Alamat laporan',
                'latitude' => '-6.2000000',
                'longitude' => '106.8166660',
                'description' => 'Air naik.',
            ])
            ->assertRedirect(route('reports.create'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'related_type' => 'disaster_report',
            'type' => 'activity',
        ]);
        $notification = $user->notifications()->firstOrFail();

        $this->get(route('notifications.index', ['filter' => 'activity']))
            ->assertOk()
            ->assertSee('Laporan bencana berhasil dicatat')
            ->assertSee(route('reports.show', $notification->related_id));
    }

    public function test_sos_submission_notifies_the_owner_without_claiming_officers_received_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('sos.confirm-selection'), ['disaster_type' => 'gempa'])
            ->assertRedirect(route('sos.confirm'));
        $this->get(route('sos.confirm'))->assertOk();

        $this->post(route('sos.store'), [
            'submission_key' => session('sos.submission_key'),
            'latitude' => '-0.9333000',
            'longitude' => '100.3250000',
        ])->assertRedirect(route('sos.sent', 1));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'related_type' => 'sos_request',
            'related_id' => 1,
            'type' => 'activity',
        ]);

        $this->get(route('notifications.index', ['filter' => 'activity']))
            ->assertOk()
            ->assertSee('Permintaan SOS tercatat di aplikasi')
            ->assertSee('tidak menyatakan petugas telah menerima permintaan.');
    }

    public function test_web_notification_feed_hides_sos_activity_when_its_request_is_deleted(): void
    {
        $user = User::factory()->create();
        $sosRequest = SosRequest::query()->create([
            'user_id' => $user->id,
            'disaster_type' => 'gempa',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'urgency_level' => 'high',
            'status' => 'sent',
            'requested_at' => now(),
        ]);
        $user->notifications()->create([
            'title' => 'Permintaan SOS tercatat di aplikasi',
            'message' => 'Permintaan SOS #'.$sosRequest->id.' berhasil dicatat.',
            'type' => 'activity',
            'related_type' => 'sos_request',
            'related_id' => $sosRequest->id,
        ]);
        $user->notifications()->create([
            'title' => 'Aktivitas yang masih berlaku',
            'message' => 'Tetap terlihat.',
            'type' => 'activity',
        ]);

        DB::table('sos_requests')->where('id', $sosRequest->id)->delete();

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'activity']))
            ->assertOk()
            ->assertSee('Aktivitas yang masih berlaku')
            ->assertDontSee('Permintaan SOS tercatat di aplikasi');
    }

    public function test_source_urls_are_rendered_as_escaped_links(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'title' => '<script>alert(1)</script>', 'message' => '<img src=x onerror=alert(1)>', 'type' => 'activity',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    private function mockExternalFeeds(): void
    {
        $this->instance(BmkgNowcastService::class, \Mockery::mock(BmkgNowcastService::class, function ($mock): void {
            $mock->shouldReceive('warnings')->andReturn(['status' => 'unavailable', 'items' => []]);
        }));
        $this->instance(BmkgEarthquakeService::class, \Mockery::mock(BmkgEarthquakeService::class, function ($mock): void {
            $mock->shouldReceive('earthquakes')->andReturn(['status' => 'unavailable', 'items' => []]);
        }));
        $this->instance(BnpbNewsService::class, \Mockery::mock(BnpbNewsService::class, function ($mock): void {
            $mock->shouldReceive('news')->andReturn(['status' => 'unavailable', 'items' => []]);
        }));
    }
}
