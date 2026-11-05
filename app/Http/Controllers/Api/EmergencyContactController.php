<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmergencyContactResource;
use App\Models\EmergencyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contacts = EmergencyContact::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'category',
                'phone',
                'description',
                'region',
                'address',
                'website_url',
            ]);

        return response()->json([
            'data' => EmergencyContactResource::collection($contacts)->resolve($request),
        ]);
    }
}
