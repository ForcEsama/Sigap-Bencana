<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RiskZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RiskMapController extends Controller
{
    public function index(): View
    {
        $requiredColumns = [
            'id',
            'name',
            'disaster_type',
            'risk_level',
            'region_name',
            'latitude',
            'longitude',
            'radius_meters',
            'source',
            'data_date',
            'is_active',
        ];

        $missingColumns = Schema::hasTable('risk_zones')
            ? array_values(array_filter(
                $requiredColumns,
                fn (string $column): bool => ! Schema::hasColumn('risk_zones', $column),
            ))
            : ['risk_zones'];

        if ($missingColumns !== []) {
            Log::error('Risk map table is missing required columns.', [
                'missing_columns' => $missingColumns,
            ]);

            $zones = collect();
        } else {
            $zones = RiskZone::query()
                ->where('is_active', true)
                ->select($requiredColumns)
                ->orderBy('region_name')
                ->orderBy('name')
                ->get();
        }

        $mapZones = $zones->map(fn (RiskZone $zone): array => [
            'name' => $zone->name,
            'disaster_type' => $zone->disaster_type,
            'risk_level' => $zone->risk_level,
            'region_name' => $zone->region_name,
            'latitude' => $zone->latitude,
            'longitude' => $zone->longitude,
            'radius_meters' => $zone->radius_meters,
            'source' => $zone->source,
            'data_date' => $zone->data_date?->format('Y-m-d'),
        ])->values();

        return view('risk-map.index', compact('zones', 'mapZones'));
    }
}
