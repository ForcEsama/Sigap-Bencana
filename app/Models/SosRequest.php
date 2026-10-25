<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SosRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'disaster_type',
        'description',
        'latitude',
        'longitude',
        'address',
        'urgency_level',
        'status',
        'assigned_to',
        'requested_at',
        'acknowledged_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'requested_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Pengguna yang meminta bantuan
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Petugas yang ditugaskan
    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    // Riwayat penanganan SOS
    public function histories(): HasMany
    {
        return $this->hasMany(
            IncidentHistory::class
        );
    }

    public function reference(): string
    {
        $year = $this->requested_at?->format('Y') ?? now()->format('Y');

        return sprintf('SOS-%s-%06d', $year, $this->getKey());
    }
}
