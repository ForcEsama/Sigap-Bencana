<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ValidateEvacuationPostsCommandTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    public function test_manifest_error_returns_nonzero_and_machine_readable_json_without_database_access(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST,1,2\n");
        $manifest = $this->temporaryFile('{broken');

        $exit = Artisan::call('evacuation-posts:validate', ['file' => $file, '--format' => 'csv', '--manifest' => $manifest]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $exit);
        self::assertSame('manifest_invalid', $report['errors'][0]['code']);
        self::assertFalse($report['overall_passed']);
    }

    public function test_database_read_failure_makes_overall_result_fail_and_outputs_valid_json(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n");
        $manifest = $this->temporaryFile('{"source_crs":"EPSG:4326"}');
        DB::shouldReceive('table')->once()->with('evacuation_posts')->andThrow(new \RuntimeException('simulated read failure'));

        $exit = Artisan::call('evacuation-posts:validate', ['file' => $file, '--format' => 'csv', '--manifest' => $manifest]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $exit);
        self::assertTrue($report['validation_passed'], json_encode($report));
        self::assertFalse($report['database_check_passed']);
        self::assertFalse($report['overall_passed']);
        self::assertSame('failed', $report['overall_status']);
        self::assertSame(20, $report['database_candidate_limit_per_record']);
    }

    public function test_validation_error_returns_nonzero_and_does_not_query_database(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,999,2\n");
        $manifest = $this->temporaryFile('{"source_crs":"EPSG:4326"}');

        $exit = Artisan::call('evacuation-posts:validate', ['file' => $file, '--format' => 'csv', '--manifest' => $manifest]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $exit);
        self::assertFalse($report['validation_passed']);
        self::assertNull($report['database_check_passed']);
        self::assertFalse($report['overall_passed']);
        self::assertContains('latitude_out_of_range', array_column($report['errors'], 'code'));
    }

    public function test_successful_read_only_lookup_reports_candidate_cap_and_truncation(): void
    {
        $file = $this->temporaryFile("name,address,latitude,longitude\nUNIT TEST ONLY,TEST ADDRESS,1.25,2.5\n");
        $manifest = $this->temporaryFile('{"source_crs":"EPSG:4326"}');
        $candidates = [];
        for ($id = 1; $id <= 21; $id++) {
            $candidates[] = ['id' => $id, 'name' => 'UNIT TEST ONLY', 'address' => 'TEST ADDRESS', 'latitude' => 1.25, 'longitude' => 2.5];
        }

        $query = Mockery::mock();
        $group = Mockery::mock();
        $nested = Mockery::mock();
        $nested->shouldReceive('whereRaw')->twice()->andReturnSelf();
        $nested->shouldReceive('where')->twice()->andReturnSelf();
        $group->shouldReceive('where')->once()->andReturnUsing(function ($callback) use ($nested, $group) {
            $callback($nested);

            return $group;
        });
        $group->shouldReceive('orWhere')->once()->andReturnUsing(function ($callback) use ($nested, $group) {
            $callback($nested);

            return $group;
        });
        $query->shouldReceive('select')->once()->with(['id', 'name', 'address', 'latitude', 'longitude'])->andReturnSelf();
        $query->shouldReceive('where')->once()->andReturnUsing(function ($callback) use ($group, $query) {
            $callback($group);

            return $query;
        });
        $query->shouldReceive('limit')->once()->with(21)->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect($candidates));
        DB::shouldReceive('table')->once()->with('evacuation_posts')->andReturn($query);

        $exit = Artisan::call('evacuation-posts:validate', ['file' => $file, '--format' => 'csv', '--manifest' => $manifest]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exit);
        self::assertTrue($report['database_check_passed']);
        self::assertTrue($report['overall_passed']);
        self::assertSame(20, $report['database_duplicate_candidate_count']);
        self::assertSame(20, $report['database_candidate_limit_per_record']);
        self::assertTrue($report['database_candidates_truncated']);
    }

    private function temporaryFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'evacuation-command-test-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }
}
