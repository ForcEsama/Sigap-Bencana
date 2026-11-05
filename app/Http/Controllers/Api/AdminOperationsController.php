<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisasterReportResource;
use App\Http\Resources\SosRequestResource;
use App\Models\DisasterReport;
use App\Models\SosRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOperationsController extends Controller
{
    public function queue(Request $request): JsonResponse
    {
        $reports = DisasterReport::query()
            ->with(['user', 'assignedOfficer', 'assignment'])
            ->where(function ($query): void {
                $query->where('verification_status', 'pending')
                    ->orWhere(function ($verified): void {
                        $verified->where('verification_status', 'verified')
                            ->where('handling_status', '!=', 'resolved');
                    });
            })
            ->latest()
            ->limit(50)
            ->get();

        $sosRequests = SosRequest::query()
            ->with(['user', 'assignedOfficer'])
            ->whereIn('status', ['sent', 'assigned', 'en_route', 'arrived'])
            ->latest('requested_at')
            ->limit(50)
            ->get();

        $fieldOfficers = User::query()
            ->where('role', UserRole::PetugasLapangan->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => [
                'reports' => DisasterReportResource::collection($reports)->resolve($request),
                'sos' => SosRequestResource::collection($sosRequests)->resolve($request),
                'field_officers' => $fieldOfficers,
            ],
        ]);
    }
}
