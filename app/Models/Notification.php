<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'related_type',
        'related_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'related_id' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    // Pengguna penerima notifikasi
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
