<?php

namespace App\Services;

use JsonException;
use Throwable;

/** Read-only parser and validator for evacuation-place source files. */
class EvacuationPostDataValidator
{
    public const DEFAULT_MAX_BYTES = 10485760;

    private const REQUIRED_FIELDS = ['name', 'address', 'latitude', 'longitude'];

    private const TABLE_FIELDS = [
        'name', 'description', 'address', 'latitude', 'longitude', 'capacity',
        'current_occupancy', 'facilities', 'contact_person', 'contact_phone', 'status',
    ];

    private const FACT_FIELDS = [
        'capacity', 'current_occupancy', 'facilities', 'contact_person', 'contact_phone', 'status',
    ];

    private const UNMAPPED_SOURCE_FIELDS = [
        'site_type' => 'Tabel evacuation_posts tidak memiliki kolom jenis TES/TEA.',
        'source_name' => 'Tabel evacuation_posts tidak memiliki kolom nama sumber.',
        'source_url' => 'Tabel evacuation_posts tidak memiliki kolom URL sumber.',
        'source_checked_at' => 'Tabel evacuation_posts tidak memiliki kolom tanggal pemeriksaan sumber.',
    ];

    /**
     * @param  callable(array<string, mixed>): iterable<object|array>|null  $candidateLookup  Read-only duplicate lookup.
     * @return array<string, mixed>
     */
    public function validate(string $file, ?string $format = null, ?array $manifest = null, ?callable $candidateLookup = null, int $maxBytes = self::DEFAULT_MAX_BYTES): array
    {
        $report = [
            'file' => basename($file),
            'format' => null,
            'source_crs' => null,
            'max_bytes' => $maxBytes,
            'file_bytes' => null,
            'records_total' => 0,
            'records_valid' => 0,
            'errors' => [],
            'warnings' => [],
            'unmapped_attributes' => [],
            'missing_factual_values' => [],
            'duplicate_groups_in_file' => [],
            'database_duplicate_candidates' => [],
            'database_check_passed' => null,
            'database_check_status' => 'not_run',
            'database_candidate_limit_per_record' => 20,
            'database_candidates_truncated' => false,
            'schema_storage_caveats' => [
                'current_occupancy' => 'Kolom non-null memiliki default 0; nilai kosong bukan fakta dan bukan nilai tersimpan yang tervalidasi.',
                'status' => 'Kolom memiliki default active; nilai kosong bukan fakta dan bukan nilai tersimpan yang tervalidasi.',
            ],
            'coverage_assessment' => 'Tidak dinilai; validasi rentang koordinat tidak membuktikan lokasi berada di Kota Padang.',
        ];

        if (! is_file($file) || ! is_readable($file)) {
            $this->addError($report, null, 'file', 'file_unreadable', 'File tidak ditemukan atau tidak dapat dibaca.');

            return $this->finalizeReport($report);
        }

        if ($maxBytes < 1) {
            $this->addError($report, null, 'max_bytes', 'size_limit_invalid', 'Batas ukuran harus bilangan positif.');

            return $this->finalizeReport($report);
        }
        $fileSize = @filesize($file);
        if ($fileSize === false) {
            $this->addError($report, null, 'file', 'file_size_unavailable', 'Ukuran file tidak dapat diperiksa.');

            return $this->finalizeReport($report);
        }
        $report['file_bytes'] = $fileSize;
        if ($fileSize > $maxBytes) {
            $this->addError($report, null, 'file', 'file_too_large', 'Ukuran file melewati batas; isi tidak dibaca.');

            return $this->finalizeReport($report);
        }
        $contents = @file_get_contents($file, false, null, 0, $maxBytes + 1);
        if ($contents === false) {
            $this->addError($report, null, 'file', 'file_read_failed', 'File tidak dapat dibaca.');

            return $this->finalizeReport($report);
        }
        if (strlen($contents) > $maxBytes) {
            $this->addError($report, null, 'file', 'file_too_large', 'File membesar melewati batas saat dibaca; isi tidak diproses.');

            return $this->finalizeReport($report);
        }
        $report['file_bytes'] = strlen($contents);
        if ($contents === '') {
            $this->addError($report, null, 'file', 'file_empty', 'File kosong.');

            return $this->finalizeReport($report);
        }
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $this->addError($report, null, 'file', 'utf8_invalid', 'File harus berisi teks UTF-8 yang valid.');

            return $this->finalizeReport($report);
        }

        $format = strtolower(trim((string) ($format ?: pathinfo($file, PATHINFO_EXTENSION))));
        $format = $format === 'json' ? 'geojson' : $format;
        $report['format'] = $format;

        try {
            $manifest ??= [];
            $manifestCrs = $manifest['source_crs'] ?? null;
            if ($manifestCrs !== null && ! is_string($manifestCrs)) {
                $this->addError($report, null, 'source_crs', 'crs_invalid', 'source_crs pada manifest harus berupa string.');
                $manifestCrs = null;
            }
            if ($format === 'csv') {
                $rows = $this->readCsv($contents, $report);
                $declaredCrs = $manifestCrs;
            } elseif ($format === 'geojson') {
                [$rows, $documentCrs] = $this->readGeoJson($contents, $report);
                if ($documentCrs !== null && $manifestCrs !== null
                    && $this->normalizeCrs($documentCrs) !== $this->normalizeCrs($manifestCrs)) {
                    $this->addError($report, null, 'source_crs', 'crs_conflict', 'CRS GeoJSON berbeda dengan CRS pada manifest.');
                }
                $declaredCrs = $manifestCrs ?? $documentCrs;
            } else {
                $this->addError($report, null, 'format', 'format_unsupported', 'Format harus csv atau geojson.');

                return $this->finalizeReport($report);
            }
        } catch (Throwable $exception) {
            $this->addError($report, null, 'file', 'parse_failed', 'File tidak dapat diparsing: '.$exception->getMessage());

            return $this->finalizeReport($report);
        }

        $report['source_crs'] = $declaredCrs;
        $normalizedCrs = $this->normalizeCrs($declaredCrs);
        if ($declaredCrs === null || trim($declaredCrs) === '') {
            $this->addError($report, null, 'source_crs', 'crs_missing', 'CRS sumber wajib dinyatakan dalam manifest atau metadata GeoJSON.');
        } elseif (str_starts_with((string) $normalizedCrs, 'UNKNOWN:')) {
            $this->addError($report, null, 'source_crs', 'crs_unknown', 'CRS sumber tidak dikenal; gunakan identifier CRS yang terdokumentasi.');
        } elseif ($normalizedCrs !== 'EPSG:4326') {
            $this->addError($report, null, 'source_crs', 'crs_unsupported', 'Tahap ini hanya menerima WGS 84 / EPSG:4326; tidak ada reproyeksi otomatis.');
        }

        $report['records_total'] = count($rows);
        if ($rows === []) {
            $this->addError($report, null, 'records', 'records_empty', 'Tidak ada record yang dapat divalidasi.');
        }
        $unmappedCounts = [];
        $missingFacts = array_fill_keys(self::FACT_FIELDS, 0);
        $fingerprints = [];

        foreach ($rows as $index => $row) {
            $recordNumber = $row['_record_number'] ?? ($index + 1);
            $errorsBeforeRecord = count($report['errors']);
            $properties = $row['properties'] ?? $row;
            $coordinates = $row['_coordinates'] ?? null;

            foreach ($properties as $field => $value) {
                if (! str_starts_with((string) $field, '_') && ! in_array($field, self::TABLE_FIELDS, true)) {
                    $unmappedCounts[$field] = ($unmappedCounts[$field] ?? 0) + (filled($value) ? 1 : 0);
                }
            }

            foreach (self::REQUIRED_FIELDS as $field) {
                $value = in_array($field, ['latitude', 'longitude'], true)
                    ? ($coordinates[$field] ?? $properties[$field] ?? null)
                    : ($properties[$field] ?? null);
                if ((! is_string($value) && ! is_numeric($value)) || trim((string) $value) === '') {
                    $this->addError($report, $recordNumber, $field, 'required', 'Field wajib kosong atau tidak valid.');
                }
            }

            foreach (['name', 'address'] as $field) {
                if (isset($properties[$field]) && $properties[$field] !== '' && ! is_string($properties[$field])) {
                    $this->addError($report, $recordNumber, $field, 'type_invalid', $field.' harus berupa teks.');
                }
            }
            foreach (['name' => 255, 'contact_person' => 255, 'contact_phone' => 20, 'status' => 20] as $field => $limit) {
                if (isset($properties[$field]) && $properties[$field] !== '') {
                    if (! is_string($properties[$field])) {
                        $this->addError($report, $recordNumber, $field, 'type_invalid', $field.' harus berupa teks.');
                    } elseif (mb_strlen($properties[$field], 'UTF-8') > $limit) {
                        $this->addError($report, $recordNumber, $field, 'length_exceeded', $field.' melebihi batas '.$limit.' karakter skema.');
                    }
                }
            }
            foreach (['description', 'facilities'] as $field) {
                if (isset($properties[$field]) && $properties[$field] !== '' && ! is_string($properties[$field])) {
                    $this->addError($report, $recordNumber, $field, 'type_invalid', $field.' harus berupa teks.');
                }
            }
            foreach (['capacity', 'current_occupancy'] as $field) {
                if (isset($properties[$field]) && $properties[$field] !== '' && ! $this->validUnsignedInteger($properties[$field])) {
                    $this->addError($report, $recordNumber, $field, 'unsigned_integer_invalid', $field.' harus bilangan bulat nonnegatif dalam batas kolom unsigned integer.');
                }
            }

            foreach (self::FACT_FIELDS as $field) {
                if (! array_key_exists($field, $properties) || ! filled($properties[$field])) {
                    $missingFacts[$field]++;
                }
            }

            $latitude = $coordinates['latitude'] ?? $properties['latitude'] ?? null;
            $longitude = $coordinates['longitude'] ?? $properties['longitude'] ?? null;
            if (! $this->validateCoordinates($latitude, $longitude, $recordNumber, $report)) {
                continue;
            }

            if ($normalizedCrs !== 'EPSG:4326') {
                continue;
            }

            $key = $this->fingerprint($properties, $latitude, $longitude);
            $fingerprints[$key][] = $recordNumber;

            if ($candidateLookup !== null && count($report['errors']) === $errorsBeforeRecord) {
                try {
                    foreach ($candidateLookup([
                        'name' => trim((string) ($properties['name'] ?? '')),
                        'address' => trim((string) ($properties['address'] ?? '')),
                        'latitude' => (float) $latitude,
                        'longitude' => (float) $longitude,
                    ]) as $candidate) {
                        $candidate = (array) $candidate;
                        $reasons = [];
                        if ($this->normalizeText($candidate['name'] ?? '') === $this->normalizeText($properties['name'] ?? '')
                            && $this->normalizeText($candidate['address'] ?? '') === $this->normalizeText($properties['address'] ?? '')) {
                            $reasons[] = 'nama_dan_alamat';
                        }
                        if (isset($candidate['latitude'], $candidate['longitude'])
                            && number_format((float) $candidate['latitude'], 7, '.', '') === number_format((float) $latitude, 7, '.', '')
                            && number_format((float) $candidate['longitude'], 7, '.', '') === number_format((float) $longitude, 7, '.', '')) {
                            $reasons[] = 'koordinat';
                        }
                        if ($reasons !== []) {
                            $report['database_duplicate_candidates'][] = [
                                'record' => $recordNumber,
                                'database_id' => $candidate['id'] ?? null,
                                'match_type' => count($reasons) === 2 ? 'exact_match' : 'candidate',
                                'matched_by' => $reasons,
                            ];
                        }
                    }
                } catch (Throwable) {
                    $this->addWarning($report, $recordNumber, 'database_duplicate_check_failed', 'Pemeriksaan duplikasi database gagal; hasil bukan berarti tidak ada duplikasi.');
                }
            }
        }

        foreach ($fingerprints as $recordNumbers) {
            if (count($recordNumbers) > 1) {
                $report['duplicate_groups_in_file'][] = ['records' => $recordNumbers];
                $this->addWarning($report, $recordNumbers[0], 'duplicate_in_file', 'Baris memiliki nama, alamat, dan koordinat sama setelah normalisasi.');
            }
        }

        foreach (self::UNMAPPED_SOURCE_FIELDS as $field => $reason) {
            $report['unmapped_attributes'][] = [
                'field' => $field,
                'records_with_value' => $unmappedCounts[$field] ?? 0,
                'manifest_value_present' => isset($manifest[$field])
                    && (is_string($manifest[$field]) || is_numeric($manifest[$field]))
                    && trim((string) $manifest[$field]) !== '',
                'reason' => $reason,
            ];
        }
        foreach ($unmappedCounts as $field => $count) {
            if (! isset(self::UNMAPPED_SOURCE_FIELDS[$field]) && $count > 0) {
                $report['unmapped_attributes'][] = [
                    'field' => $field,
                    'records_with_value' => $count,
                    'reason' => 'Tidak ada pemetaan ke kolom evacuation_posts.',
                ];
            }
        }

        foreach ($missingFacts as $field => $count) {
            if ($count > 0) {
                $report['missing_factual_values'][] = [
                    'field' => $field,
                    'records_missing_value' => $count,
                    'note' => 'Nilai kosong tidak boleh diganti dengan default yang dianggap fakta.',
                ];
            }
        }

        $invalidRecords = [];
        foreach ($report['errors'] as $error) {
            if ($error['record'] !== null) {
                $invalidRecords[$error['record']] = true;
            }
        }
        $report['records_valid'] = max(0, $report['records_total'] - count($invalidRecords));
        if ($normalizedCrs !== 'EPSG:4326') {
            $report['records_valid'] = 0;
        }
        $report['database_duplicate_candidates'] = array_values(array_unique($report['database_duplicate_candidates'], SORT_REGULAR));

        return $this->finalizeReport($report);
    }

    /** @return array<string, mixed> */
    private function finalizeReport(array $report): array
    {
        $report['error_count'] = count($report['errors']);
        $report['warning_count'] = count($report['warnings']);
        $report['unmapped_attribute_count'] = count($report['unmapped_attributes']);
        $report['duplicate_group_count'] = count($report['duplicate_groups_in_file']);
        $report['database_duplicate_candidate_count'] = count($report['database_duplicate_candidates']);
        $report['records_valid'] ??= 0;
        $report['validation_passed'] = $report['error_count'] === 0 && $report['records_valid'] > 0;
        $report['overall_passed'] = $report['validation_passed'] && $report['database_check_passed'] === true;
        $report['overall_status'] = $report['overall_passed'] ? 'passed' : 'failed';

        return $report;
    }

    /** @return list<array<string, mixed>> */
    private function readCsv(string $contents, array &$report): array
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('File CSV tidak dapat dibuka.');
        }
        fwrite($handle, $contents);
        rewind($handle);

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                $this->addError($report, null, 'header', 'header_missing', 'CSV tidak memiliki header.');

                return [];
            }
            $headers = array_map(static fn ($header): string => trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);
            if (count(array_filter($headers, static fn ($header) => $header !== '')) === 0) {
                $this->addError($report, null, 'header', 'header_missing', 'CSV tidak memiliki nama header.');

                return [];
            }
            if (count(array_unique($headers)) !== count($headers)) {
                $this->addError($report, null, 'header', 'header_duplicate', 'Nama kolom CSV tidak boleh duplikat.');

                return [];
            }
            foreach (self::REQUIRED_FIELDS as $required) {
                if (! in_array($required, $headers, true)) {
                    $this->addError($report, null, $required, 'header_required', 'Kolom wajib tidak ditemukan pada header CSV.');
                }
            }

            $rows = [];
            $recordNumber = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $recordNumber++;
                if ($values === [null] || $values === []) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    $this->addError($report, $recordNumber, 'row', 'column_count_mismatch', 'Jumlah nilai tidak sama dengan jumlah header.');
                    $values = array_slice(array_pad($values, count($headers), null), 0, count($headers));
                }
                $row = array_combine($headers, $values);
                $row['_record_number'] = $recordNumber;
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /** @return array{0: list<array<string, mixed>>, 1: ?string} */
    private function readGeoJson(string $contents, array &$report): array
    {
        try {
            $document = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('JSON tidak valid: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($document) || ($document['type'] ?? null) !== 'FeatureCollection' || ! is_array($document['features'] ?? null)) {
            $this->addError($report, null, 'type', 'feature_collection_required', 'GeoJSON harus FeatureCollection dengan array features.');

            return [[], null];
        }

        $crs = $document['crs']['properties']['name'] ?? $document['crs']['name'] ?? null;
        $rows = [];
        foreach ($document['features'] as $index => $feature) {
            $recordNumber = $index + 1;
            if (! is_array($feature) || ($feature['type'] ?? null) !== 'Feature') {
                $this->addError($report, $recordNumber, 'feature', 'feature_required', 'Setiap item harus bertipe Feature.');

                continue;
            }
            $properties = $feature['properties'] ?? [];
            if (! is_array($properties)) {
                $this->addError($report, $recordNumber, 'properties', 'properties_invalid', 'Properties harus berupa object.');
                $properties = [];
            }
            $geometry = $feature['geometry'] ?? null;
            $coordinates = null;
            if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'Point') {
                $this->addError($report, $recordNumber, 'geometry', 'point_required', 'Geometri setiap feature harus Point.');
            } else {
                $position = $geometry['coordinates'] ?? null;
                if (! is_array($position) || count($position) !== 2
                    || (! is_int($position[0] ?? null) && ! is_float($position[0] ?? null))
                    || (! is_int($position[1] ?? null) && ! is_float($position[1] ?? null))) {
                    $this->addError($report, $recordNumber, 'coordinates', 'position_invalid', 'Point harus memakai koordinat [longitude, latitude].');
                } else {
                    $coordinates = ['longitude' => $position[0], 'latitude' => $position[1]];
                }
            }
            $rows[] = [
                'properties' => $properties,
                '_coordinates' => $coordinates,
                '_record_number' => $recordNumber,
            ];
        }

        return [$rows, is_string($crs) ? $crs : null];
    }

    private function validateCoordinates(mixed $latitude, mixed $longitude, int|string $record, array &$report): bool
    {
        $lat = $this->parseCoordinate($latitude);
        $lon = $this->parseCoordinate($longitude);
        if ($lat === null || $lon === null) {
            if ($lat !== null && abs($lat) <= 180 && abs($lat) > 90 && $lon !== null && abs($lon) <= 90) {
                $this->addError($report, $record, 'coordinates', 'coordinates_suspected_swapped', 'Koordinat mungkin tertukar. Periksa sumber; command tidak menukar koordinat.');
            } else {
                $this->addError($report, $record, 'coordinates', 'coordinates_invalid', 'Koordinat harus angka desimal dengan maksimal 7 angka pecahan.');
            }

            return false;
        }
        if (abs($lat) > 90 && abs($lat) <= 180 && abs($lon) <= 90) {
            $this->addError($report, $record, 'coordinates', 'coordinates_suspected_swapped', 'Koordinat mungkin tertukar. Periksa sumber; command tidak menukar koordinat.');

            return false;
        }
        if ($lat < -90 || $lat > 90) {
            $this->addError($report, $record, 'latitude', 'latitude_out_of_range', 'Latitude harus berada pada rentang -90 sampai 90.');

            return false;
        }
        if ($lon < -180 || $lon > 180) {
            $this->addError($report, $record, 'longitude', 'longitude_out_of_range', 'Longitude harus berada pada rentang -180 sampai 180.');

            return false;
        }

        return true;
    }

    private function parseCoordinate(mixed $value): ?float
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/', $value) !== 1) {
            return null;
        }
        $fraction = explode('.', $value, 2)[1] ?? '';
        if (strlen($fraction) > 7) {
            return null;
        }

        return (float) $value;
    }

    private function validUnsignedInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 0 && $value <= 4294967295;
        }
        if (! is_string($value) || preg_match('/^\\d+$/', trim($value)) !== 1) {
            return false;
        }
        $normalized = ltrim(trim($value), '0') ?: '0';

        return strlen($normalized) < 10 || (strlen($normalized) === 10 && strcmp($normalized, '4294967295') <= 0);
    }

    private function normalizeCrs(?string $crs): ?string
    {
        if ($crs === null || trim($crs) === '') {
            return null;
        }
        $crs = strtolower(trim($crs));
        $aliases = [
            'epsg:4326' => 'EPSG:4326',
            'urn:ogc:def:crs:epsg::4326' => 'EPSG:4326',
            'http://www.opengis.net/def/crs/epsg/0/4326' => 'EPSG:4326',
            'https://www.opengis.net/def/crs/epsg/0/4326' => 'EPSG:4326',
            'crs84' => 'EPSG:4326',
            'ogc:crs84' => 'EPSG:4326',
            'urn:ogc:def:crs:ogc:1.3:crs84' => 'EPSG:4326',
            'epsg:3857' => 'EPSG:3857',
            'urn:ogc:def:crs:epsg::3857' => 'EPSG:3857',
            'http://www.opengis.net/def/crs/epsg/0/3857' => 'EPSG:3857',
            'https://www.opengis.net/def/crs/epsg/0/3857' => 'EPSG:3857',
        ];

        return $aliases[$crs] ?? 'UNKNOWN:'.$crs;
    }

    private function fingerprint(array $properties, mixed $latitude, mixed $longitude): string
    {
        return implode('|', [
            $this->normalizeText($properties['name'] ?? ''),
            $this->normalizeText($properties['address'] ?? ''),
            number_format((float) $latitude, 7, '.', ''),
            number_format((float) $longitude, 7, '.', ''),
        ]);
    }

    private function normalizeText(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function addError(array &$report, int|string|null $record, string $field, string $code, string $message): void
    {
        $report['errors'][] = compact('record', 'field', 'code', 'message');
    }

    private function addWarning(array &$report, int|string|null $record, string $code, string $message): void
    {
        $report['warnings'][] = compact('record', 'code', 'message');
    }
}
