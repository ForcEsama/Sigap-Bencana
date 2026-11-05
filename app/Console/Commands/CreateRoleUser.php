<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sigap:create-role-user {name} {email} {role}')]
#[Description('Buat akun aktif untuk salah satu dari tiga role SIGAP BENCANA')]
class CreateRoleUser extends Command
{
    public function handle(): int
    {
        $name = trim($this->argument('name'));
        $email = trim($this->argument('email'));
        $role = UserRole::tryFrom($this->argument('role'));

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Nama dan alamat email yang valid wajib diberikan.');

            return self::FAILURE;
        }

        if ($role === null) {
            $this->error('Role harus masyarakat, admin, atau petugas_lapangan.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Email tersebut sudah terdaftar. Tidak ada akun yang diubah.');

            return self::FAILURE;
        }

        $password = bin2hex(random_bytes(24));
        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role->value,
            'is_active' => true,
        ]);

        $this->info('Akun berhasil dibuat. Simpan kata sandi berikut; kata sandi hanya ditampilkan pada proses ini.');
        $this->line('Email: '.$email);
        $this->line('Role: '.$role->value);
        $this->line('Kata sandi: '.$password);

        return self::SUCCESS;
    }
}
