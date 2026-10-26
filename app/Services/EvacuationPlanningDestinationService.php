<?php

namespace App\Services;

class EvacuationPlanningDestinationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
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

            $headers = $this->normalizeHeaders($headers);
            $required = [
                'record_type', 'location_code', 'site_type', 'name', 'address',
                'description', 'capacity', 'latitude', 'longitude', 'status',
                'evacuation_route', 'source_document', 'source_page',
                'verification_status', 'manual_review_note',
            ];
            if (count(array_unique($headers)) !== count($headers)
                || array_diff($required, $headers) !== []) {
                return [];
            }

            $photoFiles = [
                'A1' => 'tea-a1.jpg', 'A2' => 'tea-a2.jpg',
                'B1' => 'tea-b1.jpg', 'B2' => 'tea-b2.jpg',
                'C1' => 'tea-c1.jpg', 'D1' => 'tea-d1.jpg',
                'D2' => 'tea-d2.jpg', 'E1' => 'tea-e1.jpg',
                'F1' => 'tea-f1.jpg', 'G1' => 'tea-g1.jpg',
                'H1' => 'tea-h1.jpg', 'I1' => 'tea-i1.jpg',
            ];
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

                $locationCode = trim((string) $row['location_code']);
                $capacity = trim((string) ($row['capacity'] ?? ''));
                $photoFilename = $photoFiles[$locationCode] ?? null;
                $imagePath = $photoFilename !== null
                    && is_file(public_path('images/evacuation/'.$photoFilename))
                        ? 'images/evacuation/'.$photoFilename
                        : null;
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
                    'image_path' => $imagePath,
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

            $headers = $this->normalizeHeaders($headers);
            $required = ['location_code', 'latitude', 'longitude', 'verification_status'];
            if (count(array_unique($headers)) !== count($headers)
                || array_diff($required, $headers) !== []) {
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

    /** @param list<string|null> $headers
     * @return list<string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(
            static fn ($header): string => trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"),
            $headers,
        );
    }
}
