<?php

namespace Tests\Feature;

use App\Models\DisasterReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebDisasterReportTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'disaster_type' => 'banjir',
            'address' => '  Jalan Merdeka, Jakarta  ',
            'latitude' => '-6.2000000',
            'longitude' => '106.8166660',
            'description' => 'Air mulai memasuki rumah warga di lingkungan ini.',
            'occurred_at' => null,
        ];
    }

    public function test_guest_cannot_open_or_submit_a_report(): void
    {
        $this->get(route('reports.create'))->assertRedirect(route('login'));
        $this->post(route('reports.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_authenticated_user_can_open_the_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Lapor Kejadian')
            ->assertSee('Gunakan lokasi saya')
            ->assertSee('Panggilan Darurat Nasional');
    }

    public function test_report_is_saved_with_server_owned_metadata_and_generated_title(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reports.store'), [
                ...$this->validPayload(),
                'user_id' => $otherUser->id,
                'title' => 'Judul dari browser',
                'verification_status' => 'verified',
                'handling_status' => 'resolved',
                'verified_by' => $otherUser->id,
                'verified_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('disaster_reports', 1);
        $report = DisasterReport::query()->firstOrFail();

        $this->assertSame($user->id, $report->user_id);
        $this->assertStringStartsWith('Laporan Banjir - ', $report->title);
        $this->assertNotSame('Judul dari browser', $report->title);
        $this->assertSame('pending', $report->verification_status);
        $this->assertSame('unhandled', $report->handling_status);
        $this->assertNull($report->verified_by);
        $this->assertNull($report->verified_at);
        $this->assertSame('Jalan Merdeka, Jakarta', $report->address);
    }

    public function test_invalid_category_is_rejected_without_creating_a_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [...$this->validPayload(), 'disaster_type' => 'unknown'])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors('disaster_type');

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_generated_title_uses_occurrence_time_when_provided(): void
    {
        $occurrenceTime = now()->subDay()->startOfMinute();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), [
                ...$this->validPayload(),
                'occurred_at' => $occurrenceTime->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('reports.create'));

        $this->assertSame(
            'Laporan Banjir - '.$occurrenceTime->locale('id')->translatedFormat('d F Y H:i'),
            DisasterReport::query()->firstOrFail()->title,
        );
    }

    public function test_blank_address_and_description_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [...$this->validPayload(), 'address' => '', 'description' => ''])
            ->assertSessionHasErrors(['address', 'description']);

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_missing_coordinates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [...$this->validPayload(), 'latitude' => '', 'longitude' => ''])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_coordinates_outside_valid_ranges_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [...$this->validPayload(), 'latitude' => '-90.001', 'longitude' => '180.001'])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_coordinates_with_more_than_seven_decimal_places_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [...$this->validPayload(), 'latitude' => '-6.20000001'])
            ->assertSessionHasErrors('latitude');

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_future_occurrence_time_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [...$this->validPayload(), 'occurred_at' => now()->addDay()->format('Y-m-d H:i:s')])
            ->assertSessionHasErrors('occurred_at');

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_valid_photo_is_stored_on_the_private_local_disk(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), [
                ...$this->validPayload(),
                'photo' => UploadedFile::fake()->image('evidence.jpg'),
            ])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHas('success');

        $path = DisasterReport::query()->firstOrFail()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith('disaster-reports/', $path);
    }

    public function test_non_image_and_video_uploads_are_rejected(): void
    {
        Storage::fake('local');

        foreach ([
            UploadedFile::fake()->create('document.txt', 10, 'text/plain'),
            UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
        ] as $upload) {
            $this->actingAs(User::factory()->create())
                ->from(route('reports.create'))
                ->post(route('reports.store'), [...$this->validPayload(), 'photo' => $upload])
                ->assertSessionHasErrors('photo');
        }

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_photo_over_ten_megabytes_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [
                ...$this->validPayload(),
                'photo' => UploadedFile::fake()->image('large.png')->size(10241),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('disaster_reports', 0);
    }

    public function test_storage_failure_does_not_create_a_report_or_show_success(): void
    {
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), [
                ...$this->validPayload(),
                'photo' => UploadedFile::fake()->image('evidence.png'),
            ])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors('photo')
            ->assertSessionMissing('success');

        $this->assertDatabaseCount('disaster_reports', 0);
    }
}
