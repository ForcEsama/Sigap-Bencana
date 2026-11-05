<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->whereIn('role', [
            'administrator',
            'operator',
            'editor',
            'pengelola_evakuasi',
        ])->update(['role' => 'admin']);

        DB::table('users')->whereIn('role', [
            'petugas',
            'petugas_tanggap_darurat',
        ])->update(['role' => 'petugas_lapangan']);

        DB::table('users')->whereNotIn('role', [
            'masyarakat',
            'admin',
            'petugas_lapangan',
        ])->update(['role' => 'masyarakat']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Role normalization is intentionally irreversible; reverting would
        // require restoring the exact previous role assigned to each account.
    }
};
