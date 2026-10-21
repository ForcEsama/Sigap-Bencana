
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_histories', function (Blueprint $table) {
            $table->id();

            // Riwayat dapat terkait laporan bencana atau permintaan SOS
            $table->foreignId('disaster_report_id')
                ->nullable()
                ->constrained('disaster_reports')
                ->cascadeOnDelete();

            $table->foreignId('sos_request_id')
                ->nullable()
                ->constrained('sos_requests')
                ->cascadeOnDelete();

            // Pengguna atau petugas yang melakukan perubahan
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_histories');
    }
};
