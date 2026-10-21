<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisasterReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'disaster_type',
        'description',
        'address',
        'latitude',
        'longitude',
        'occurred_at',
        'photo_path',
        'verification_status',
        'handling_status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'occurred_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // Masyarakat yang membuat laporan
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Petugas yang memverifikasi laporan
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    // Riwayat perubahan status laporan
    public function histories(): HasMany
    {
        return $this->hasMany(
            IncidentHistory::class
        );
    }
}
