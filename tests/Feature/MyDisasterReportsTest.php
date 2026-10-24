<?php

namespace Tests\Feature;

use App\Models\DisasterReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyDisasterReportsTest extends TestCase
{
    use RefreshDatabase;

    private function createReport(User $owner, array $attributes = []): DisasterReport
    {
        return DisasterReport::query()->create([
            'user_id' => $owner->id,
            'title' => 'Laporan banjir di Jalan Merdeka',
            'disaster_type' => 'banjir',
            'description' => 'Deskripsi untuk kebutuhan test.',
            'address' => 'Jalan Merdeka, Jakarta',
            'latitude' => '-6.2000000',
            'longitude' => '106.8166660',
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
            ...$attributes,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_report_history(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_reports_sees_the_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Riwayat Laporan Saya')
            ->assertSee('Belum ada laporan')
            ->assertSee(route('reports.create'));
    }

    public function test_user_sees_own_report_and_its_statuses(): void
    {
        $user = User::factory()->create();
        $this->createReport($user, [
            'title' => 'Laporan Banjir Saya',
            'verification_status' => 'verified',
            'handling_status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Laporan Banjir Saya')
            ->assertSee('Banjir')
            ->assertSee('Jalan Merdeka, Jakarta')
            ->assertSee('Terverifikasi')
            ->assertSee('Sedang ditangani');
    }

    public function test_reports_from_other_users_are_not_shown(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createReport($user, ['title' => 'Laporan Milik Saya']);
        $this->createReport($otherUser, [
            'title' => 'Laporan Rahasia Pengguna Lain',
            'address' => 'Alamat Milik Pengguna Lain',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Laporan Milik Saya')
            ->assertDontSee('Laporan Rahasia Pengguna Lain')
            ->assertDontSee('Alamat Milik Pengguna Lain');
    }

    public function test_pagination_contains_only_the_authenticated_users_reports(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            $this->createReport($user, ['title' => 'Laporan Saya '.$index]);
        }

        for ($index = 1; $index <= 3; $index++) {
            $this->createReport($otherUser, ['title' => 'Laporan Orang Lain '.$index]);
        }

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Laporan Saya')
            ->assertSee('page=2')
            ->assertDontSee('Laporan Orang Lain');

        $this->get(route('reports.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Laporan Saya')
            ->assertDontSee('Laporan Orang Lain');
    }

    public function test_report_history_does_not_expose_photo_paths_or_photo_urls(): void
    {
        $user = User::factory()->create();
        $this->createReport($user, [
            'photo_path' => 'disaster-reports/private-evidence-unique.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));
        $response->assertOk()
            ->assertDontSee('private-evidence-unique.jpg')
            ->assertDontSee('/storage/disaster-reports/');
    }
}
