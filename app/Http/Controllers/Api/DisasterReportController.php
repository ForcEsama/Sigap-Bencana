<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisasterReportResource;
use App\Models\DisasterReport;
use App\Services\DisasterOperationsService;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        abort_unless($request->user()->role === UserRole::Masyarakat->value, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'disaster_type' => ['required', 'string', 'in:gempa,banjir,longsor,kebakaran,tsunami,lainnya'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $report = DisasterReport::query()->create([
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

        return response()->json(['data' => $report]);
    }

    public function verify(Request $request, int $id, DisasterOperationsService $operations): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'notes' => [Rule::requiredIf($request->input('decision') === 'rejected'), 'nullable', 'string', 'max:2000'],
        ]);

        $report = $operations->verifyReport(
            $request->user(),
            $id,
            $validated['decision'],
            $validated['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Status verifikasi laporan berhasil diperbarui.',
            'data' => new DisasterReportResource($report),
        ]);
    }

    public function assign(Request $request, int $id, DisasterOperationsService $operations): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'petugas_lapangan')->where('is_active', true),
            ],
        ]);

        $report = $operations->assignReport($request->user(), $id, (int) $validated['assigned_to']);

        return response()->json([
            'message' => 'Laporan berhasil ditugaskan kepada petugas lapangan.',
            'data' => new DisasterReportResource($report),
        ]);
    }

    public function updateHandlingStatus(
        Request $request,
        int $id,
        DisasterOperationsService $operations,
    ): JsonResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'resolved'])],
            'notes' => [
                Rule::requiredIf($request->input('status') === 'resolved'),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $report = $operations->updateReportHandling(
            $request->user(),
            $id,
            $validated['status'],
            $validated['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Status penanganan laporan berhasil diperbarui.',
            'data' => new DisasterReportResource($report),
        ]);
    }
}
