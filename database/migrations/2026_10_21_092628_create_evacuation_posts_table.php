
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evacuation_posts', function (Blueprint $table) {
            $table->id();

            // Informasi posko evakuasi
            $table->string('name');
            $table->text('description')->nullable();

            // Alamat dan lokasi
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Kapasitas dan fasilitas
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('current_occupancy')
                ->default(0);
            $table->text('facilities')->nullable();

            // Informasi pengelola
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 20)->nullable();

            // Status operasional posko
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->index(['status', 'name']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_posts');
    }
};
