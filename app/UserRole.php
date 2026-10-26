<?php

namespace App;

enum UserRole: string
{
    case Masyarakat = 'masyarakat';
    case Admin = 'admin';
    case PetugasLapangan = 'petugas_lapangan';

    public function isOperational(): bool
    {
        return $this !== self::Masyarakat;
    }

    public function label(): string
    {
        return match ($this) {
            self::Masyarakat => 'Masyarakat',
            self::Admin => 'Admin',
            self::PetugasLapangan => 'Petugas Lapangan',
        };
    }

    public function dashboardSummary(): string
    {
        return match ($this) {
            self::Masyarakat => 'Informasi dan layanan bencana untuk Anda.',
            self::Admin => 'Pantau laporan masyarakat, permintaan SOS, serta penugasan petugas.',
            self::PetugasLapangan => 'Tinjau tugas yang ditugaskan kepada Anda dan catat progres penanganan.',
        };
    }
}
