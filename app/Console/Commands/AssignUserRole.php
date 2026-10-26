<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sigap:user-role {email : Email akun yang akan diperbarui} {role : masyarakat, admin, atau petugas_lapangan} {--force : Lewati konfirmasi perubahan role}')]
#[Description('Ubah role akun tanpa mengubah kata sandi atau data pengguna')]
class AssignUserRole extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if ($user === null) {
            $this->error('Akun dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        $role = UserRole::tryFrom($this->argument('role'));
        if ($role === null) {
            $this->error('Role harus masyarakat, admin, atau petugas_lapangan.');

            return self::FAILURE;
        }

        if ($user->role === $role->value) {
            $this->info('Akun sudah menggunakan role '.$role->value.'.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Ubah role '.$user->email.' menjadi '.$role->value.'?')) {
            $this->warn('Perubahan dibatalkan.');

            return self::SUCCESS;
        }

        $user->role = $role->value;
        $user->save();

        $this->info('Role akun berhasil diperbarui.');

        return self::SUCCESS;
    }
}
