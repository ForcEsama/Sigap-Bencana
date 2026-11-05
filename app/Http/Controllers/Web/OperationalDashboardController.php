<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DisasterReport;
use App\Models\DisasterReportAssignment;
use App\Models\SosRequest;
use App\Models\User;
use App\Services\DisasterOperationsService;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $role = UserRole::from($request->user()->role);
        $data = ['role' => $role, 'summary' => $role->dashboardSummary()];

        if ($role === UserRole::Admin) {
            $data += [
                'pendingReportCount' => DisasterReport::query()->where('verification_status', 'pending')->count(),
                'openSosCount' => SosRequest::query()->whereIn('status', ['sent', 'assigned', 'en_route', 'arrived'])->count(),
                'fieldOfficerCount' => User::query()->where('role', UserRole::PetugasLapangan->value)->where('is_active', true)->count(),
                'reports' => DisasterReport::query()
                    ->with(['user', 'assignedOfficer', 'assignment'])
                    ->where(function ($query): void {
                        $query->where('verification_status', 'pending')
                            ->orWhere(function ($verified): void {
                                $verified->where('verification_status', 'verified')
                                    ->where('handling_status', '!=', 'resolved');
                            });
                    })
                    ->latest()
                    ->limit(20)
                    ->get(),
                'sosRequests' => SosRequest::query()
                    ->with(['user', 'assignedOfficer'])
                    ->whereIn('status', ['sent', 'assigned', 'en_route', 'arrived'])
                    ->latest('requested_at')
                    ->limit(20)
                    ->get(),
                'fieldOfficers' => User::query()
                    ->where('role', UserRole::PetugasLapangan->value)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'managedUsers' => User::query()
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get(['id', 'name', 'email', 'role', 'is_active']),
            ];
        } else {
            $data += [
                'reports' => DisasterReport::query()
                    ->with(['user', 'assignedOfficer', 'assignment'])
                    ->whereHas('assignment', fn ($query) => $query->where('assigned_to', $request->user()->id))
                    ->where('verification_status', 'verified')
                    ->where('handling_status', '!=', 'resolved')
                    ->latest()
                    ->get(),
                'sosRequests' => SosRequest::query()
                    ->with(['user', 'assignedOfficer', 'histories.actor'])
                    ->where('assigned_to', $request->user()->id)
                    ->whereIn('status', ['assigned', 'en_route', 'arrived'])
                    ->latest('requested_at')
                    ->get(),
            ];
        }

        return view('operational-dashboard.index', $data);
    }

    public function verifyReport(Request $request, int $reportId, DisasterOperationsService $operations): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'notes' => [Rule::requiredIf($request->input('decision') === 'rejected'), 'nullable', 'string', 'max:2000'],
        ]);
        $operations->verifyReport($request->user(), $reportId, $validated['decision'], $validated['notes'] ?? null);

        return back()->with('success', 'Keputusan verifikasi laporan disimpan.');
    }

    public function assignReport(Request $request, int $reportId, DisasterOperationsService $operations): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'petugas_lapangan')->where('is_active', true)],
        ]);
        $operations->assignReport($request->user(), $reportId, (int) $validated['assigned_to']);

        return back()->with('success', 'Laporan ditugaskan kepada petugas lapangan.');
    }

    public function updateReportHandling(Request $request, int $reportId, DisasterOperationsService $operations): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'resolved'])],
            'notes' => [Rule::requiredIf($request->input('status') === 'resolved'), 'nullable', 'string', 'max:2000'],
        ]);
        $operations->updateReportHandling($request->user(), $reportId, $validated['status'], $validated['notes'] ?? null);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }

    public function updateSos(Request $request, int $sosId, DisasterOperationsService $operations): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['assigned', 'en_route', 'arrived', 'completed', 'rejected'])],
            'assigned_to' => [
                Rule::requiredIf($request->input('status') === 'assigned'),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'petugas_lapangan')->where('is_active', true),
            ],
            'notes' => [Rule::requiredIf(in_array($request->input('status'), ['completed', 'rejected'], true)), 'nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'assigned') {
            $operations->assignSos($request->user(), $sosId, (int) $validated['assigned_to']);
        } else {
            $operations->updateSosStatus($request->user(), $sosId, $validated['status'], $validated['notes'] ?? null);
        }

        return back()->with('success', 'Permintaan SOS berhasil diperbarui.');
    }

    public function updateFieldSos(Request $request, int $sosId, DisasterOperationsService $operations): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['en_route', 'arrived', 'completed'])],
            'notes' => [Rule::requiredIf($request->input('status') === 'completed'), 'nullable', 'string', 'max:2000'],
        ]);
        $operations->updateSosStatus($request->user(), $sosId, $validated['status'], $validated['notes'] ?? null);

        return back()->with('success', 'Progres bantuan berhasil diperbarui.');
    }

    public function updateUserAccess(Request $request, int $userId): RedirectResponse
    {
        $user = User::query()->findOrFail($userId);
        abort_if($user->is($request->user()), 403, 'Admin tidak dapat mengubah akses akunnya sendiri.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => ['required', 'boolean'],
        ]);

        $hasOpenAssignment = DisasterReportAssignment::query()
            ->where('assigned_to', $user->id)
            ->whereHas('report', fn ($query) => $query
                ->where('verification_status', 'verified')
                ->where('handling_status', '!=', 'resolved'))
            ->exists()
            || SosRequest::query()
                ->where('assigned_to', $user->id)
                ->whereIn('status', ['assigned', 'en_route', 'arrived'])
                ->exists();

        if (
            $hasOpenAssignment
            && ($validated['role'] !== UserRole::PetugasLapangan->value || ! $validated['is_active'])
        ) {
            return back()->withErrors([
                'user_access' => 'Tugas aktif harus dialihkan atau diselesaikan sebelum role diubah atau akun dinonaktifkan.',
            ]);
        }

        $user->role = $validated['role'];
        $user->is_active = $validated['is_active'];
        $user->save();

        return back()->with('success', 'Role dan status akun berhasil diperbarui.');
    }
}
