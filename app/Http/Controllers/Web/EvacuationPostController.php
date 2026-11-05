<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EvacuationPost;
use App\Services\EvacuationPlanningDestinationService;
use Illuminate\View\View;

class EvacuationPostController extends Controller
{
    public function index(EvacuationPlanningDestinationService $planningDestinationService): View
    {
        $planningDestinations = $planningDestinationService->all();

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
            ])
            ->map(fn (EvacuationPost $post): array => $this->toViewData($post));

        $areas = $posts
            ->pluck('address')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('evacuation-posts.index', compact('posts', 'areas', 'planningDestinations'));
    }

    public function show(int $evacuationPost): View
    {
        $post = EvacuationPost::query()->findOrFail($evacuationPost);

        return view('evacuation-posts.show', [
            'post' => $this->toViewData($post),
        ]);
    }

    /** @return array<string, mixed> */
    private function toViewData(EvacuationPost $post): array
    {
        $latitude = is_numeric($post->latitude) ? (float) $post->latitude : null;
        $longitude = is_numeric($post->longitude) ? (float) $post->longitude : null;
        $coordinatesAreValid = $latitude !== null
            && $longitude !== null
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;

        return [
            'id' => $post->id,
            'name' => $post->name,
            'description' => $post->description,
            'address' => $post->address,
            'latitude' => $coordinatesAreValid ? $latitude : null,
            'longitude' => $coordinatesAreValid ? $longitude : null,
            'has_valid_coordinates' => $coordinatesAreValid,
            'capacity' => $post->capacity,
            'current_occupancy' => $post->current_occupancy,
            'facilities' => $post->facilities,
            'contact_person' => $post->contact_person,
            'contact_phone' => $post->contact_phone,
            'status' => $post->status,
            'detail_url' => route('evacuation-posts.show', $post->id),
        ];
    }
}
