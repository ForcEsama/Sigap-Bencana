<?php

namespace App\Console\Commands;

use App\Services\EvacuationPostDataValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ValidateEvacuationPostsFile extends Command
{
    protected $signature = 'evacuation-posts:validate
        {file : Path to a CSV or GeoJSON source file}
        {--format= : csv or geojson; inferred from file extension if omitted}
        {--manifest= : JSON manifest declaring source metadata and CRS}
        {--max-bytes=10485760 : Maximum input file size in bytes}';

    protected $description = 'Validate evacuation-post CSV/GeoJSON without writing to the database';

    public function handle(EvacuationPostDataValidator $validator): int
    {
        $manifest = [];
        $manifestFailure = null;
        if ($manifestPath = $this->option('manifest')) {
            if (! is_file($manifestPath) || ! is_readable($manifestPath)) {
                $manifestFailure = 'Manifest tidak ditemukan atau tidak dapat dibaca.';
            } else {
                try {
                    $manifestContents = file_get_contents($manifestPath);
                    if ($manifestContents === false || ! mb_check_encoding($manifestContents, 'UTF-8')) {
                        throw new \RuntimeException('Manifest harus berisi UTF-8 yang valid.');
                    }
                    $manifest = json_decode($manifestContents, true, 512, JSON_THROW_ON_ERROR);
                    if (! is_array($manifest)) {
                        throw new \RuntimeException('Manifest harus berupa object JSON.');
                    }
                } catch (Throwable $exception) {
                    $manifestFailure = 'Manifest tidak valid: '.$exception->getMessage();
                }
            }
        }

        $maxBytes = filter_var($this->option('max-bytes'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($maxBytes === false) {
            $this->writeJson($this->failureReport('max_bytes', 'size_limit_invalid', '--max-bytes harus bilangan bulat positif.'));

            return self::FAILURE;
        }
        if ($manifestFailure !== null) {
            $this->writeJson($this->failureReport('manifest', 'manifest_invalid', $manifestFailure));

            return self::FAILURE;
        }

        $databaseCheckError = null;
        $databaseCheckAttempted = false;
        $databaseCandidatesTruncated = false;
        $candidateLookup = function (array $record) use (&$databaseCheckError, &$databaseCheckAttempted, &$databaseCandidatesTruncated): iterable {
            try {
                $databaseCheckAttempted = true;
                $name = mb_strtolower(trim($record['name']));
                $address = mb_strtolower(trim($record['address']));
                $latitude = number_format($record['latitude'], 7, '.', '');
                $longitude = number_format($record['longitude'], 7, '.', '');

                $results = DB::table('evacuation_posts')
                    ->select(['id', 'name', 'address', 'latitude', 'longitude'])
                    ->where(function ($query) use ($name, $address, $latitude, $longitude): void {
                        $query->where(function ($match) use ($name, $address): void {
                            $match->whereRaw('LOWER(TRIM(name)) = ?', [$name])
                                ->whereRaw('LOWER(TRIM(address)) = ?', [$address]);
                        })->orWhere(function ($match) use ($latitude, $longitude): void {
                            $match->where('latitude', $latitude)
                                ->where('longitude', $longitude);
                        });
                    })
                    ->limit(21)
                    ->get()
                    ->all();
                if (count($results) > 20) {
                    $databaseCandidatesTruncated = true;

                    return array_slice($results, 0, 20);
                }

                return $results;
            } catch (Throwable $exception) {
                $databaseCheckError = $exception->getMessage();

                return [];
            }
        };

        $report = $validator->validate(
            (string) $this->argument('file'),
            $this->option('format'),
            $manifest,
            $candidateLookup,
            $maxBytes,
        );

        $report['database_check_passed'] = ! $databaseCheckAttempted ? null : $databaseCheckError === null;
        $report['database_check_status'] = ! $databaseCheckAttempted ? 'not_run' : ($databaseCheckError === null ? 'passed' : 'failed');
        $report['database_candidate_limit_per_record'] = 20;
        $report['database_candidates_truncated'] = $databaseCandidatesTruncated;

        if ($databaseCheckError !== null) {
            $report['database_check_failed'] = true;
            $report['database_check_message'] = 'Kueri read-only kandidat duplikasi gagal; hasil tidak berarti duplikasi tidak ada.';
            $report['warnings'][] = [
                'record' => null,
                'code' => 'database_duplicate_check_failed',
                'message' => $report['database_check_message'],
            ];
            $report['warning_count'] = count($report['warnings']);
        }

        $report['overall_passed'] = $report['validation_passed'] && $report['database_check_passed'] === true;
        $report['overall_status'] = $report['overall_passed'] ? 'passed' : 'failed';
        $this->writeJson($report);

        return $report['overall_passed'] ? self::SUCCESS : self::FAILURE;
    }

    private function writeJson(array $report): void
    {
        try {
            $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $json = '{"validation_passed":false,"database_check_passed":null,"overall_passed":false,"overall_status":"failed","errors":[{"record":null,"field":"report","code":"json_encoding_failed","message":"Laporan gagal dikodekan sebagai JSON."}],"warnings":[]}';
        }
        $this->line($json);
    }

    private function failureReport(string $field, string $code, string $message): array
    {
        $report = [
            'file' => basename((string) $this->argument('file')),
            'format' => null,
            'source_crs' => null,
            'max_bytes' => null,
            'file_bytes' => null,
            'records_total' => 0,
            'records_valid' => 0,
            'errors' => [['record' => null, 'field' => $field, 'code' => $code, 'message' => $message]],
            'warnings' => [],
            'unmapped_attributes' => [],
            'unmapped_attribute_count' => 0,
            'missing_factual_values' => [],
            'duplicate_groups_in_file' => [],
            'duplicate_group_count' => 0,
            'database_duplicate_candidates' => [],
            'database_duplicate_candidate_count' => 0,
            'database_check_passed' => null,
            'database_check_status' => 'not_run',
            'database_candidate_limit_per_record' => 20,
            'database_candidates_truncated' => false,
            'schema_storage_caveats' => [
                'current_occupancy' => 'Kolom non-null memiliki default 0; nilai kosong bukan fakta.',
                'status' => 'Kolom memiliki default active; nilai kosong bukan fakta.',
            ],
            'coverage_assessment' => 'Tidak dinilai; rentang koordinat tidak membuktikan lokasi berada di Kota Padang.',
            'error_count' => 1,
            'warning_count' => 0,
            'validation_passed' => false,
            'overall_passed' => false,
            'overall_status' => 'failed',
        ];

        return $report;
    }
}
