<?php

namespace App\Services;

use App\Models\DisasterReport;
use App\Models\SosRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;

class DisasterOperationsService
{
    public function verifyReport(
        User $actor,
        int $reportId,
        string $decision,
        ?string $notes,
    ): DisasterReport {
        abort_unless($actor->role === UserRole::Admin->value, 403);

        return DB::transaction(function () use ($actor, $reportId, $decision, $notes): DisasterReport {
            $report = DisasterReport::query()->lockForUpdate()->findOrFail($reportId);
            abort_unless($report->verification_status === 'pending', 409, 'Laporan sudah pernah diverifikasi.');

            $oldStatus = $report->verification_status;
            $report->verification_status = $decision;
            $report->verified_by = $actor->id;
            $report->verified_at = now();
            $report->save();

            $report->histories()->create([
                'actor_id' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => $decision,
                'notes' => $notes,
            ]);

            $this->notify(
                $report->user,
                'Status laporan diperbarui',
                $decision === 'verified'
                    ? 'Laporan kejadian Anda telah diverifikasi.'
                    : 'Laporan kejadian Anda ditolak.'.($notes ? ' Alasan: '.$notes : ''),
                'disaster_report',
                $report->id,
            );

            return $report->load(['user', 'assignedOfficer', 'assignment']);
        });
    }

    public function assignReport(User $actor, int $reportId, int $officerId): DisasterReport
    {
        abort_unless($actor->role === UserRole::Admin->value, 403);

        return DB::transaction(function () use ($actor, $reportId, $officerId): DisasterReport {
            $officer = $this->activeFieldOfficer($officerId);
            $report = DisasterReport::query()->lockForUpdate()->findOrFail($reportId);
            abort_unless($report->verification_status === 'verified', 409, 'Verifikasi laporan sebelum menugaskan petugas.');

            $assignment = $report->assignment()->firstOrNew();
            $previousOfficerId = $assignment->assigned_to;
            $assignment->assigned_to = $officer->id;
            $assignment->assigned_by = $actor->id;
            $assignment->save();

            $report->histories()->create([
                'actor_id' => $actor->id,
                'old_status' => $report->handling_status,
                'new_status' => $report->handling_status,
                'notes' => $previousOfficerId === null
                    ? 'Ditugaskan kepada '.$officer->name.'.'
                    : 'Penugasan dialihkan kepada '.$officer->name.'.',
            ]);

            $this->notify($officer, 'Tugas laporan baru', 'Laporan '.$report->title.' ditugaskan kepada Anda.', 'disaster_report', $report->id);
            $this->notify($report->user, 'Laporan ditugaskan', 'Laporan '.$report->title.' telah ditugaskan kepada petugas lapangan.', 'disaster_report', $report->id);

            return $report->load(['user', 'assignedOfficer', 'assignment']);
        });
    }

    public function updateReportHandling(
        User $actor,
        int $reportId,
        string $newStatus,
        ?string $notes,
    ): DisasterReport {
        abort_unless(in_array($actor->role, [UserRole::Admin->value, UserRole::PetugasLapangan->value], true), 403);

        return DB::transaction(function () use ($actor, $reportId, $newStatus, $notes): DisasterReport {
            $report = DisasterReport::query()->lockForUpdate()->findOrFail($reportId);
            abort_unless($report->verification_status === 'verified', 409, 'Verifikasi laporan sebelum mengubah penanganan.');

            if ($actor->role === UserRole::PetugasLapangan->value) {
                abort_unless(
                    (int) $report->assignment()->value('assigned_to') === (int) $actor->id,
                    403,
                    'Petugas hanya dapat memperbarui laporan yang ditugaskan kepadanya.',
                );
            }

            $allowedTransitions = [
                'unhandled' => ['in_progress'],
                'in_progress' => ['resolved'],
                'resolved' => [],
            ];
            $oldStatus = $report->handling_status;
            abort_unless(in_array($newStatus, $allowedTransitions[$oldStatus] ?? [], true), 409, 'Perubahan status penanganan tidak sesuai urutan.');

            $report->handling_status = $newStatus;
            $report->save();
            $report->histories()->create([
                'actor_id' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
            ]);

            $this->notify(
                $report->user,
                'Perkembangan laporan diperbarui',
                'Status laporan '.$report->title.' berubah menjadi '.$this->reportStatusLabel($newStatus).'.'.($notes ? ' Catatan: '.$notes : ''),
                'disaster_report',
                $report->id,
            );

            return $report->load(['user', 'assignedOfficer']);
        });
    }

    public function assignSos(User $actor, int $sosId, int $officerId): SosRequest
    {
        abort_unless($actor->role === UserRole::Admin->value, 403);

        return DB::transaction(function () use ($actor, $sosId, $officerId): SosRequest {
            $officer = $this->activeFieldOfficer($officerId);
            $sos = SosRequest::query()->lockForUpdate()->findOrFail($sosId);
            abort_unless(in_array($sos->status, ['sent', 'assigned'], true), 409, 'SOS ini sudah mulai ditangani dan tidak dapat dialihkan dari tahap ini.');

            $oldStatus = $sos->status;
            $sos->assigned_to = $officer->id;
            $sos->status = 'assigned';
            $sos->acknowledged_at ??= now();
            $sos->save();

            $sos->histories()->create([
                'actor_id' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => 'assigned',
                'notes' => 'Ditugaskan kepada '.$officer->name.'.',
            ]);

            $this->notify($sos->user, 'Petugas ditugaskan', $sos->reference().' telah ditugaskan kepada petugas lapangan.', 'sos_request', $sos->id);
            $this->notify($officer, 'Tugas SOS baru', $sos->reference().' ditugaskan kepada Anda.', 'sos_request', $sos->id);

            return $sos->load(['user', 'assignedOfficer', 'histories.actor']);
        });
    }

    public function updateSosStatus(
        User $actor,
        int $sosId,
        string $newStatus,
        ?string $notes,
    ): SosRequest {
        abort_unless(in_array($actor->role, [UserRole::Admin->value, UserRole::PetugasLapangan->value], true), 403);

        return DB::transaction(function () use ($actor, $sosId, $newStatus, $notes): SosRequest {
            $sos = SosRequest::query()->lockForUpdate()->findOrFail($sosId);
            $oldStatus = $sos->status;

            if ($actor->role === UserRole::Admin->value) {
                abort_unless($oldStatus === 'sent' && $newStatus === 'rejected', 403, 'Admin dapat menolak SOS yang belum ditugaskan; penanganan lapangan dilakukan petugas.');
            } else {
                abort_unless((int) $sos->assigned_to === (int) $actor->id, 403, 'Petugas hanya dapat memperbarui SOS yang ditugaskan kepadanya.');
                $allowedTransitions = [
                    'assigned' => ['en_route'],
                    'en_route' => ['arrived'],
                    'arrived' => ['completed'],
                ];
                abort_unless(in_array($newStatus, $allowedTransitions[$oldStatus] ?? [], true), 409, 'Perubahan status SOS tidak sesuai urutan.');
            }

            $sos->status = $newStatus;
            if ($newStatus === 'completed') {
                $sos->completed_at = now();
            }
            $sos->save();

            $sos->histories()->create([
                'actor_id' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
            ]);

            $this->notify(
                $sos->user,
                self::SOS_STATUS_LABELS[$newStatus],
                $sos->reference().': '.self::SOS_STATUS_LABELS[$newStatus].($notes ? ' '.$notes : ''),
                'sos_request',
                $sos->id,
            );

            return $sos->load(['user', 'assignedOfficer', 'histories.actor']);
        });
    }

    private const SOS_STATUS_LABELS = [
        'sent' => 'Permintaan SOS tersimpan',
        'assigned' => 'Petugas ditugaskan',
        'en_route' => 'Petugas menuju lokasi',
        'arrived' => 'Petugas tiba di lokasi',
        'completed' => 'Penanganan selesai',
        'rejected' => 'Permintaan tidak dapat ditindaklanjuti',
    ];

    private function activeFieldOfficer(int $userId): User
    {
        return User::query()
            ->whereKey($userId)
            ->where('role', UserRole::PetugasLapangan->value)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function notify(User $user, string $title, string $message, string $relatedType, int $relatedId): void
    {
        $user->notifications()->create([
            'title' => $title,
            'message' => $message,
            'type' => 'Aktivitas',
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
    }

    private function reportStatusLabel(string $status): string
    {
        return match ($status) {
            'unhandled' => 'Belum ditangani',
            'in_progress' => 'Dalam penanganan',
            'resolved' => 'Selesai ditangani',
            default => 'Status diperbarui',
        };
    }
}
