<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RiskZone;
use Illuminate\View\View;

class RiskMapController extends Controller
{
    public function index(): View
    {
        $zones = RiskZone::query()
            ->where('is_active', true)
            ->select([
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
            ])
            ->orderBy('region_name')
            ->orderBy('name')
            ->get();

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
