<?php

namespace Tests\Feature;

use App\Models\DisasterReport;
use App\Models\DisasterReportAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_field_officer_see_role_specific_dashboards(): void
    {
        $roles = [
            'admin' => ['Admin', 'Laporan kejadian'],
            'petugas_lapangan' => ['Petugas Lapangan', 'Laporan tugas saya'],
        ];

        foreach ($roles as $role => [$label, $capability]) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('operational.dashboard'))
                ->assertOk()
                ->assertSee($capability)
                ->assertSee($label);
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('operational.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_masyarakat_cannot_open_the_operational_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'masyarakat']))
            ->get(route('operational.dashboard'))
            ->assertForbidden();
    }

    public function test_inactive_or_unrecognized_accounts_cannot_open_the_operational_dashboard(): void
    {
        $inactiveOfficer = User::factory()->create([
            'role' => 'petugas_lapangan',
            'is_active' => false,
        ]);
        $this->actingAs($inactiveOfficer)
            ->get(route('operational.dashboard'))
            ->assertForbidden();

        $unrecognizedRole = User::factory()->create(['role' => 'mitra']);
        $this->actingAs($unrecognizedRole)
            ->get(route('operational.dashboard'))
            ->assertForbidden();
    }

    public function test_operational_role_login_lands_on_home_and_sees_dashboard_shortcut(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee(route('operational.dashboard'));
    }

    public function test_masyarakat_home_does_not_show_operational_dashboard_shortcut(): void
    {
        $user = User::factory()->create(['role' => 'masyarakat', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Dashboard Admin')
            ->assertDontSee('Dashboard Petugas');
    }

    public function test_operational_roles_can_open_dashboard_from_other_web_pages(): void
    {
        foreach (['admin' => 'Dashboard Admin', 'petugas_lapangan' => 'Dashboard Petugas'] as $role => $label) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);

            $this->actingAs($user)
                ->get(route('reports.index'))
                ->assertOk()
                ->assertSee($label)
                ->assertSee(route('operational.dashboard'));
        }
    }

    public function test_admin_can_manage_a_user_role_but_cannot_change_own_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'masyarakat', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('operational.users.access', $user->id), [
                'role' => 'petugas_lapangan',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'petugas_lapangan',
            'is_active' => true,
        ]);

        $this->patch(route('operational.users.access', $admin->id), [
            'role' => 'masyarakat',
            'is_active' => '1',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_cannot_deactivate_or_demote_field_officer_with_active_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $officer = User::factory()->create(['role' => 'petugas_lapangan', 'is_active' => true]);
        $report = DisasterReport::query()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Tugas aktif',
            'disaster_type' => 'banjir',
            'description' => 'Laporan uji.',
            'address' => 'Padang',
            'latitude' => '-0.9500000',
            'longitude' => '100.3500000',
            'verification_status' => 'verified',
            'handling_status' => 'in_progress',
        ]);
        DisasterReportAssignment::query()->create([
            'disaster_report_id' => $report->id,
            'assigned_to' => $officer->id,
            'assigned_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('operational.users.access', $officer->id), [
                'role' => 'masyarakat',
                'is_active' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('user_access');

        $this->assertDatabaseHas('users', [
            'id' => $officer->id,
            'role' => 'petugas_lapangan',
            'is_active' => true,
        ]);
    }

    public function test_console_can_assign_only_one_of_the_three_roles_without_changing_password(): void
    {
        $user = User::factory()->create([
            'role' => 'masyarakat',
            'password' => 'PasswordSebelumnya123',
        ]);
        $originalPassword = $user->fresh()->password;

        $this->artisan('sigap:user-role', [
            'email' => $user->email,
            'role' => 'petugas_lapangan',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'petugas_lapangan',
            'password' => $originalPassword,
        ]);

        $this->artisan('sigap:user-role', [
            'email' => $user->email,
            'role' => 'operator',
            '--force' => true,
        ])->assertFailed();
    }
}
