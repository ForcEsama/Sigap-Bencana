<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    
    // Laporan bencana yang dibuat pengguna
    public function disasterReports(): HasMany
    {
        return $this->hasMany(DisasterReport::class);
    }

    // Laporan bencana yang diverifikasi pengguna sebagai petugas
    public function verifiedReports(): HasMany
    {
        return $this->hasMany(
            DisasterReport::class,
            'verified_by'
        );
    }

    // Permintaan SOS yang dibuat pengguna
    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class);
    }

    // Permintaan SOS yang ditangani petugas
    public function assignedSosRequests(): HasMany
    {
        return $this->hasMany(
            SosRequest::class,
            'assigned_to'
        );
    }

    // Berita yang ditulis pengguna
    public function newsArticles(): HasMany
    {
        return $this->hasMany(News::class, 'author_id');
    }

    // Notifikasi milik pengguna
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // Riwayat perubahan yang dilakukan pengguna
    public function incidentHistories(): HasMany
    {
        return $this->hasMany(
            IncidentHistory::class,
            'actor_id'
        );
    }

    // Peringatan dini yang dibuat pengguna
    public function earlyWarnings(): HasMany
    {
        return $this->hasMany(
            EarlyWarning::class,
            'created_by'
        );
    }

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
