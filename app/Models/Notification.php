<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

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

    #[Scope]
    protected function withValidSosReference(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNull('related_type')
                ->orWhere('related_type', '<>', 'sos_request')
                ->orWhereExists(function (QueryBuilder $sosRequests): void {
                    $sosRequests->selectRaw('1')
                        ->from('sos_requests')
                        ->whereColumn('sos_requests.id', 'notifications.related_id')
                        ->whereColumn('sos_requests.user_id', 'notifications.user_id');
                });
        });
    }
}
