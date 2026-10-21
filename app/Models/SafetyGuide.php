<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'disaster_type',
        'phase',
        'summary',
        'content',
        'source',
        'source_url',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
