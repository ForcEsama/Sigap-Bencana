
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            // Penulis berita
            $table->foreignId('author_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Informasi utama berita
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');

            // Gambar dan kategori
            $table->string('image_path')->nullable();
            $table->string('category', 50)->nullable();

            // Sumber informasi
            $table->string('source')->nullable();
            $table->string('source_url', 2048)->nullable();

            // Status publikasi
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
