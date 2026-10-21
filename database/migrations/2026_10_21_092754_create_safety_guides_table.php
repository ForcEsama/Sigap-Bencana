
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_guides', function (Blueprint $table) {
            $table->id();

            // Informasi utama panduan
            $table->string('title');
            $table->string('disaster_type', 30);
            $table->string('phase', 20);
            $table->text('summary')->nullable();
            $table->longText('content');

            // Sumber panduan
            $table->string('source')->nullable();
            $table->string('source_url', 2048)->nullable();

            // Status publikasi
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'disaster_type',
                'phase',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_guides');
    }
};
