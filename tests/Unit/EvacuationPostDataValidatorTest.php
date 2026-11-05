<?php

namespace Tests\Unit;

use App\Services\EvacuationPostDataValidator;
use PHPUnit\Framework\TestCase;

class EvacuationPostDataValidatorTest extends TestCase
{
    private EvacuationPostDataValidator $validator;

    /** @var list<string> */
    private array $filesToRemove = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new EvacuationPostDataValidator;
    }

    public function test_valid_csv_is_parsed_and_unmapped_fields_are_reported(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude,site_type,source_url\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5,TES,https://example.invalid/source\n");

        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326']);

        self::assertSame(1, $report['records_total']);
        self::assertSame(1, $report['records_valid']);
        self::assertSame([], $report['errors']);
        self::assertContains('site_type', array_column($report['unmapped_attributes'], 'field'));
        self::assertContains('source_url', array_column($report['unmapped_attributes'], 'field'));
    }

    public function test_csv_without_declared_crs_is_rejected(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n");

        $report = $this->validator->validate($file, 'csv');

        self::assertContains('crs_missing', array_column($report['errors'], 'code'));
    }

    public function test_csv_requires_name_address_and_coordinate_columns(): void
    {
        $file = $this->temporaryFile("name,address,latitude\nUNIT TEST ONLY,TEST ADDRESS,1.25\n");

        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326']);

        self::assertContains('header_required', array_column($report['errors'], 'code'));
        self::assertGreaterThan(0, $report['error_count']);
    }

    public function test_geojson_point_uses_longitude_latitude_and_declared_crs(): void
    {
        $document = [
            'type' => 'FeatureCollection',
            'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            'features' => [[
                'type' => 'Feature',
                'properties' => ['name' => 'UNIT TEST ONLY', 'address' => 'TEST ADDRESS'],
                'geometry' => ['type' => 'Point', 'coordinates' => [2.5, 1.25]],
            ]],
        ];
        $file = $this->temporaryFile(json_encode($document, JSON_THROW_ON_ERROR));

        $report = $this->validator->validate($file, 'geojson');

        self::assertSame(1, $report['records_valid']);
        self::assertSame([], $report['errors']);
        self::assertSame('EPSG:4326', $report['source_crs']);
    }

    public function test_geojson_without_crs_and_non_point_geometry_are_rejected(): void
    {
        $document = [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => ['name' => 'UNIT TEST ONLY', 'address' => 'TEST ADDRESS'],
                'geometry' => ['type' => 'LineString', 'coordinates' => [[2.5, 1.25], [2.6, 1.3]]],
            ]],
        ];
        $file = $this->temporaryFile(json_encode($document, JSON_THROW_ON_ERROR));

        $report = $this->validator->validate($file, 'geojson');

        self::assertContains('crs_missing', array_column($report['errors'], 'code'));
        self::assertContains('point_required', array_column($report['errors'], 'code'));
    }

    public function test_non_wgs84_crs_is_rejected_without_reprojection(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n");

        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:3857']);

        self::assertContains('crs_unsupported', array_column($report['errors'], 'code'));
    }

    public function test_only_documented_exact_wgs84_crs_identifiers_are_accepted(): void
    {
        foreach (['EPSG:4326', 'urn:ogc:def:crs:EPSG::4326', 'https://www.opengis.net/def/crs/EPSG/0/4326', 'OGC:CRS84'] as $crs) {
            $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n");
            $report = $this->validator->validate($file, 'csv', ['source_crs' => $crs]);
            self::assertSame(1, $report['records_valid'], $crs);
            self::assertNotContains('crs_unknown', array_column($report['errors'], 'code'));
        }
    }

    public function test_unknown_crs_that_contains_4326_is_rejected(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n");
        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'NOT-A-CRS-4326-LOOKALIKE']);
        self::assertContains('crs_unknown', array_column($report['errors'], 'code'));
        self::assertSame(0, $report['records_valid']);
    }

    public function test_geojson_manifest_and_document_crs_conflict_is_rejected(): void
    {
        $file = $this->geoJsonFile('EPSG:4326');
        $report = $this->validator->validate($file, 'geojson', ['source_crs' => 'EPSG:3857']);
        self::assertContains('crs_conflict', array_column($report['errors'], 'code'));
    }

    public function test_empty_file_invalid_utf8_duplicate_headers_and_oversize_are_rejected(): void
    {
        foreach ([['', 'file_empty'], ["name,address,latitude,longitude\n\xFF", 'utf8_invalid'], ["name,address,name,longitude\na,b,c,d\n", 'header_duplicate']] as [$contents, $expected]) {
            $report = $this->validator->validate($this->temporaryFile($contents), 'csv', ['source_crs' => 'EPSG:4326']);
            self::assertContains($expected, array_column($report['errors'], 'code'));
        }
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1,2\n");
        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326'], null, 8);
        self::assertContains('file_too_large', array_column($report['errors'], 'code'));
    }

    public function test_schema_string_limits_and_unsigned_integer_values_are_validated(): void
    {
        $header = "name,address,latitude,longitude,contact_phone,status,capacity,current_occupancy\n";
        $row = str_repeat('N', 256).',TEST ADDRESS,1,2,'.str_repeat('1', 21).','.str_repeat('s', 21).',-1,4294967296'."\n";
        $report = $this->validator->validate($this->temporaryFile($header.$row), 'csv', ['source_crs' => 'EPSG:4326']);
        $codes = array_column($report['errors'], 'code');
        self::assertContains('length_exceeded', $codes);
        self::assertContains('unsigned_integer_invalid', $codes);
    }

    public function test_duplicate_csv_headers_stop_row_validation(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude,latitude\nUNIT TEST ONLY,TEST,1,2,3\n");
        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326']);
        self::assertSame(0, $report['records_total']);
        self::assertContains('header_duplicate', array_column($report['errors'], 'code'));
        self::assertNotContains('coordinates_invalid', array_column($report['errors'], 'code'));
    }

    public function test_csv_without_header_and_malformed_geojson_have_clear_errors(): void
    {
        $csv = $this->validator->validate($this->temporaryFile("\n"), 'csv', ['source_crs' => 'EPSG:4326']);
        self::assertContains('header_missing', array_column($csv['errors'], 'code'));

        $geojson = $this->validator->validate($this->temporaryFile('{broken'), 'geojson');
        self::assertContains('parse_failed', array_column($geojson['errors'], 'code'));
    }

    private function geoJsonFile(string $crs): string
    {
        $document = ['type' => 'FeatureCollection', 'crs' => ['type' => 'name', 'properties' => ['name' => $crs]], 'features' => [['type' => 'Feature', 'properties' => ['name' => 'UNIT TEST ONLY', 'address' => 'TEST ADDRESS'], 'geometry' => ['type' => 'Point', 'coordinates' => [2.5, 1.25]]]]];

        return $this->temporaryFile(json_encode($document, JSON_THROW_ON_ERROR));
    }

    public function test_coordinate_range_precision_and_suspected_swap_are_reported(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nOUT OF RANGE,TEST ADDRESS,91,100\nLIKELY SWAPPED,TEST ADDRESS,100,1\nTOO PRECISE,TEST ADDRESS,1.12345678,2\n");

        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326']);
        $codes = array_column($report['errors'], 'code');

        self::assertContains('latitude_out_of_range', $codes);
        self::assertContains('coordinates_suspected_swapped', $codes);
        self::assertContains('coordinates_invalid', $codes);
        self::assertSame('Tidak dinilai; validasi rentang koordinat tidak membuktikan lokasi berada di Kota Padang.', $report['coverage_assessment']);
    }

    public function test_empty_required_values_and_missing_facts_are_reported(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude,capacity,current_occupancy,facilities,contact_person,contact_phone,status\n,TEST ADDRESS,1.25,2.5,,,,,,\n");

        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326']);

        self::assertContains('required', array_column($report['errors'], 'code'));
        self::assertSame(6, count($report['missing_factual_values']));
    }

    public function test_duplicate_input_rows_and_database_candidates_are_reported_read_only(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n unit test only , Test   Address ,1.2500000,2.5000000\n");
        $lookupCount = 0;
        $candidateLookup = function (array $record) use (&$lookupCount): array {
            $lookupCount++;

            return [[
                'id' => 123456,
                'name' => $record['name'],
                'address' => $record['address'],
                'latitude' => $record['latitude'],
                'longitude' => $record['longitude'],
            ]];
        };

        $report = $this->validator->validate($file, 'csv', ['source_crs' => 'EPSG:4326'], $candidateLookup);

        self::assertCount(1, $report['duplicate_groups_in_file']);
        self::assertNotEmpty($report['database_duplicate_candidates']);
        self::assertSame('exact_match', $report['database_duplicate_candidates'][0]['match_type']);
        self::assertSame(2, $lookupCount);
    }

    private function temporaryFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'evacuation-validation-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->filesToRemove[] = $path;

        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->filesToRemove as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }
}
