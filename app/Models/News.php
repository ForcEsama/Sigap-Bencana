<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'image_path',
        'category',
        'source',
        'source_url',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    // Pengguna yang menulis berita
    public function author(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'author_id'
        );
    }
}
