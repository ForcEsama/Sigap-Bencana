
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Penerima notifikasi
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Isi notifikasi
            $table->string('title');
            $table->text('message');
            $table->string('type', 50);

            // Referensi objek terkait, jika ada
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            // Status sudah dibaca atau belum
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
