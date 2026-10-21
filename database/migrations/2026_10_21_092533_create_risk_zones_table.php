
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_zones', function (Blueprint $table) {
            $table->id();

            // Informasi zona risiko
            $table->string('name');
            $table->string('disaster_type', 30);
            $table->string('risk_level', 20);

            // Deskripsi dan wilayah zona
            $table->text('description')->nullable();
            $table->text('region_name');

            // Titik koordinat pusat zona
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Radius perkiraan area dalam meter
            $table->unsignedInteger('radius_meters')->nullable();

            // Sumber data zona risiko
            $table->string('source')->nullable();
            $table->date('data_date')->nullable();

            // Mengatur apakah zona ditampilkan pada peta
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['disaster_type', 'risk_level']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_zones');
    }
};
