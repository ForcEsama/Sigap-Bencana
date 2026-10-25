<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EvacuationPost;
use Illuminate\View\View;

class EvacuationPostController extends Controller
{
    public function index(): View
    {
        $planningDestinations = $this->readPlanningDestinations();

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

    /** @return list<array<string, mixed>> */
    private function readPlanningDestinations(): array
    {
        $file = base_path('docs/EVACUATION_PADANG_PDF_DRAFT.csv');
        $coordinateCandidates = $this->readCoordinateCandidates();
        if (! is_file($file) || ! is_readable($file)) {
            return [];
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                return [];
            }
            $headers = array_map(static fn ($header): string => trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);
            $required = ['record_type', 'location_code', 'site_type', 'name', 'address', 'description', 'capacity', 'latitude', 'longitude', 'status', 'evacuation_route', 'source_document', 'source_page', 'verification_status', 'manual_review_note'];
            if (count(array_unique($headers)) !== count($headers) || array_diff($required, $headers) !== []) {
                return [];
            }

            $destinations = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || count($values) !== count($headers)) {
                    continue;
                }
                $row = array_combine($headers, $values);
                if (! is_array($row)
                    || ($row['record_type'] ?? null) !== 'planning_destination'
                    || ($row['site_type'] ?? null) !== 'TEA'
                    || ($row['verification_status'] ?? null) !== 'historical_planning_data_unverified'
                    || trim((string) ($row['name'] ?? '')) === '') {
                    continue;
                }

                $capacity = trim((string) ($row['capacity'] ?? ''));
                $locationCode = trim((string) $row['location_code']);
                $candidate = $coordinateCandidates[$locationCode] ?? null;
                $destinations[] = [
                    'location_code' => $locationCode,
                    'site_type' => 'TEA',
                    'name' => trim((string) $row['name']),
                    'address' => trim((string) $row['address']),
                    'description' => trim((string) $row['description']),
                    'capacity' => preg_match('/^\d+$/', $capacity) === 1 ? $capacity : null,
                    'latitude' => $candidate['latitude'] ?? null,
                    'longitude' => $candidate['longitude'] ?? null,
                    'has_candidate_coordinates' => $candidate !== null,
                    'status' => trim((string) ($row['status'] ?? '')),
                    'evacuation_route' => trim((string) ($row['evacuation_route'] ?? '')),
                    'source_document' => trim((string) ($row['source_document'] ?? '')),
                    'source_page' => trim((string) ($row['source_page'] ?? '')),
                    'verification_status' => 'historical_planning_data_unverified',
                    'manual_review_note' => trim((string) ($row['manual_review_note'] ?? '')),
                    'has_verified_coordinates' => false,
                ];
            }

            return $destinations;
        } finally {
            fclose($handle);
        }
    }

    /** @return array<string, array{latitude: float, longitude: float}> */
    private function readCoordinateCandidates(): array
    {
        $file = base_path('docs/EVACUATION_PADANG_COORDINATES_PRELIMINARY.csv');
        if (! is_file($file) || ! is_readable($file)) {
            return [];
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                return [];
            }

            $headers = array_map(static fn ($header): string => trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);
            $required = ['location_code', 'latitude', 'longitude', 'verification_status'];
            if (count(array_unique($headers)) !== count($headers) || array_diff($required, $headers) !== []) {
                return [];
            }

            $candidates = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || count($values) !== count($headers)) {
                    continue;
                }

                $row = array_combine($headers, $values);
                if (! is_array($row) || ($row['verification_status'] ?? '') !== 'digitized_unverified') {
                    continue;
                }

                $code = trim((string) ($row['location_code'] ?? ''));
                $latitude = trim((string) ($row['latitude'] ?? ''));
                $longitude = trim((string) ($row['longitude'] ?? ''));
                if ($code === '' || ! is_numeric($latitude) || ! is_numeric($longitude)) {
                    continue;
                }

                $latitude = (float) $latitude;
                $longitude = (float) $longitude;
                if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                    continue;
                }

                $candidates[$code] = ['latitude' => $latitude, 'longitude' => $longitude];
            }

            return $candidates;
        } finally {
            fclose($handle);
        }
    }

    public function show(int $evacuationPost): View
    {
        $post = EvacuationPost::query()->findOrFail($evacuationPost);

        return view('evacuation-posts.show', [
            'post' => $this->toViewData($post),
        ]);
    }

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
