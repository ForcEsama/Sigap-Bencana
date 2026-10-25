<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EarlyWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'disaster_type',
        'description',
        'severity',
        'affected_area',
        'latitude',
        'longitude',
        'source',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // Pengguna atau petugas yang membuat peringatan
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function sourceMetadata(): HasOne
    {
        return $this->hasOne(EarlyWarningSource::class);
    }
}
