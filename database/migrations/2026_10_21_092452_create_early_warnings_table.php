
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('early_warnings', function (Blueprint $table) {
            $table->id();

            // Pengguna atau petugas yang membuat peringatan
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Informasi utama peringatan
            $table->string('title');
            $table->string('disaster_type', 30);
            $table->text('description');

            // Tingkat risiko
            $table->string('severity', 20);

            // Wilayah terdampak
            $table->text('affected_area');

            // Koordinat opsional untuk lokasi peringatan
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Sumber informasi dan masa berlaku
            $table->string('source')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Status publikasi peringatan
            $table->string('status', 20)->default('draft');

            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['disaster_type', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_warnings');
    }
};
