<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'disaster_type',
        'risk_level',
        'description',
        'region_name',
        'latitude',
        'longitude',
        'radius_meters',
        'source',
        'data_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_meters' => 'integer',
            'data_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
