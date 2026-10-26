<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisasterReportAssignment extends Model
{
    protected $fillable = [
        'disaster_report_id',
        'assigned_to',
        'assigned_by',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(DisasterReport::class, 'disaster_report_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigningAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
