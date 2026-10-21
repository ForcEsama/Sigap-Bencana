
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();

            // Informasi instansi atau layanan
            $table->string('name');
            $table->string('category', 50);
            $table->string('phone', 30);
            $table->text('description')->nullable();

            // Wilayah layanan dan informasi tambahan
            $table->string('region')->nullable();
            $table->text('address')->nullable();
            $table->string('website_url', 2048)->nullable();

            // Pengaturan tampilan kontak
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
