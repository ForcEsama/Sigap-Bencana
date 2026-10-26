<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EvacuationPostResource;
use App\Models\EvacuationPost;
use App\Services\EvacuationPlanningDestinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvacuationPostController extends Controller
{
    public function index(
        Request $request,
        EvacuationPlanningDestinationService $planningDestinationService,
    ): JsonResponse {
        $posts = EvacuationPost::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'description',
                'address',
                'latitude',
                'longitude',
                'capacity',
                'current_occupancy',
                'facilities',
                'contact_person',
                'contact_phone',
                'status',
            ]);

        return response()->json([
            'data' => EvacuationPostResource::collection($posts)->resolve($request),
            'planning_candidates' => array_map(
                static fn (array $destination): array => [
                    ...$destination,
                    'id' => 'TEA-'.$destination['location_code'],
                    'record_type' => 'planning_candidate',
                    'is_planning_candidate' => true,
                    'recommended_capacity' => $destination['capacity'],
                    'has_valid_coordinates' => $destination['has_candidate_coordinates'],
                ],
                $planningDestinationService->all(),
            ),
        ]);
    }
}
