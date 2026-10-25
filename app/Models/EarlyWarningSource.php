<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarlyWarningSource extends Model
{
    protected $fillable = [
        'early_warning_id',
        'source_identifier',
        'source_url',
        'source_published_at',
        'urgency',
    ];

    protected function casts(): array
    {
        return ['source_published_at' => 'datetime'];
    }

    public function warning(): BelongsTo
    {
        return $this->belongsTo(EarlyWarning::class, 'early_warning_id');
    }
}
