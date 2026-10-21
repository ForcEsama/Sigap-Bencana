
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_reports', function (Blueprint $table) {
            $table->id();

            // Pengguna yang membuat laporan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Informasi kejadian
            $table->string('title');
            $table->string('disaster_type', 30);
            $table->text('description')->nullable();
            $table->text('address')->nullable();

            // Koordinat lokasi kejadian
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->timestamp('occurred_at')->nullable();
            $table->string('photo_path')->nullable();

            // Status verifikasi laporan
            $table->string('verification_status', 20)
                ->default('pending');

            // Status penanganan kejadian
            $table->string('handling_status', 20)
                ->default('unhandled');

            // Petugas yang memverifikasi laporan
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Mempercepat pencarian berdasarkan status
            $table->index([
                'verification_status',
                'handling_status',
            ]);

            $table->index([
                'latitude',
                'longitude',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_reports');
    }
};
