<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvacuationPost extends Model
{
    use HasFactory;

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'capacity' => 'integer',
            'current_occupancy' => 'integer',
        ];
    }
}
