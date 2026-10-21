<?php

namespace App\Http\Controllers\Api;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\IncidentHistory;
use App\Http\Controllers\Controller;
use App\Models\DisasterReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisasterReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = DisasterReport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($reports);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'disaster_type' => [
                'required',
                'string',
                'in:gempa,banjir,longsor,kebakaran,tsunami,lainnya',
            ],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'occurred_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],
        ]);

        $report = DisasterReport::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'verification_status' => 'pending',
            'handling_status' => 'unhandled',
        ]);

        return response()->json([
            'message' => 'Laporan bencana berhasil dikirim.',
            'data' => $report,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $report = DisasterReport::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'data' => $report,
        ]);
    }

    
    public function verify(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => [
                'required',
                Rule::in(['verified', 'rejected']),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = DB::transaction(function () use (
            $request,
            $id,
            $validated
        ) {
            $report = DisasterReport::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($report->verification_status !== 'pending') {
                abort(409, 'Laporan sudah pernah diverifikasi.');
            }

            $oldStatus = $report->verification_status;

            $report->verification_status = $validated['decision'];
            $report->verified_by = $request->user()->id;
            $report->verified_at = now();
            $report->save();

            IncidentHistory::create([
                'disaster_report_id' => $report->id,
                'sos_request_id' => null,
                'actor_id' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['decision'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return $report;
        });

        return response()->json([
            'message' => 'Status verifikasi laporan berhasil diperbarui.',
            'data' => $report,
        ]);
    }

    public function updateHandlingStatus(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['unhandled', 'in_progress', 'resolved']),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = DB::transaction(function () use (
            $request,
            $id,
            $validated
        ) {
            $report = DisasterReport::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($report->verification_status !== 'verified') {
                abort(409, 'Verifikasi laporan sebelum mengubah penanganan.');
            }

            $oldStatus = $report->handling_status;
            $newStatus = $validated['status'];

            $report->handling_status = $newStatus;
            $report->save();

            if ($oldStatus !== $newStatus) {
                IncidentHistory::create([
                    'disaster_report_id' => $report->id,
                    'sos_request_id' => null,
                    'actor_id' => $request->user()->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            return $report;
        });

        return response()->json([
            'message' => 'Status penanganan berhasil diperbarui.',
            'data' => $report,
        ]);
    }

}
