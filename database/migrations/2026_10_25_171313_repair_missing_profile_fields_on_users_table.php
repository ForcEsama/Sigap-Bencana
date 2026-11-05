<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = [
            'domicile_address' => fn (Blueprint $table) => $table->string('domicile_address')->nullable(),
            'province' => fn (Blueprint $table) => $table->string('province', 100)->nullable(),
            'regency_city' => fn (Blueprint $table) => $table->string('regency_city', 100)->nullable(),
            'district' => fn (Blueprint $table) => $table->string('district', 100)->nullable(),
            'village' => fn (Blueprint $table) => $table->string('village', 100)->nullable(),
            'postal_code' => fn (Blueprint $table) => $table->string('postal_code', 10)->nullable(),
            'date_of_birth' => fn (Blueprint $table) => $table->date('date_of_birth')->nullable(),
            'gender' => fn (Blueprint $table) => $table->string('gender', 20)->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', $definition);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep profile data intact during rollback.
    }
};
