<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisasterReportResource;
use App\Http\Resources\SosRequestResource;
use App\Models\DisasterReport;
use App\Models\SosRequest;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === UserRole::PetugasLapangan->value, 403);

        $reports = DisasterReport::query()
            ->whereHas('assignment', fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->with(['user', 'assignedOfficer', 'assignment'])
            ->latest()
            ->get();
        $sosRequests = SosRequest::query()
            ->where('assigned_to', $request->user()->id)
            ->with(['user', 'assignedOfficer', 'histories.actor'])
            ->latest('requested_at')
            ->get();

        return response()->json([
            'data' => [
                'reports' => DisasterReportResource::collection($reports)->resolve($request),
                'sos' => SosRequestResource::collection($sosRequests)->resolve($request),
            ],
        ]);
    }
}
