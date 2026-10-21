<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'disaster_report_id',
        'sos_request_id',
        'actor_id',
        'old_status',
        'new_status',
        'notes',
    ];

    // Laporan bencana terkait, jika ada
    public function disasterReport(): BelongsTo
    {
        return $this->belongsTo(
            DisasterReport::class
        );
    }

    // Permintaan SOS terkait, jika ada
    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(
            SosRequest::class
        );
    }

    // Pengguna atau petugas yang melakukan perubahan
    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_id'
        );
    }
}
