
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_requests', function (Blueprint $table) {
            $table->id();

            // Pengguna yang meminta bantuan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Informasi keadaan darurat
            $table->string('disaster_type', 30);
            $table->text('description')->nullable();

            // Lokasi pengguna ketika meminta bantuan
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('address')->nullable();

            // Tingkat urgensi
            $table->string('urgency_level', 20)
                ->default('high');

            // Status penanganan SOS
            $table->string('status', 20)
                ->default('sent');

            // Petugas yang ditugaskan menangani SOS
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indeks untuk membantu pencarian permintaan SOS
            $table->index(['status', 'urgency_level']);
            $table->index(['latitude', 'longitude']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_requests');
    }
};
