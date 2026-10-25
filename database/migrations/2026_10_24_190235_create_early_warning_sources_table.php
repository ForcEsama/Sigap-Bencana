<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('early_warning_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('early_warning_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('source_identifier')->unique();
            $table->string('source_url', 2048)->nullable();
            $table->timestamp('source_published_at')->nullable();
            $table->string('urgency', 20)->nullable();
            $table->timestamps();
        });

        DB::table('news')
            ->where('source', 'BMKG - Peringatan Dini Cuaca')
            ->where('category', 'Berita')
            ->update(['category' => 'Peringatan']);
    }

    public function down(): void
    {
        DB::table('news')
            ->where('source', 'BMKG - Peringatan Dini Cuaca')
            ->where('category', 'Peringatan')
            ->update(['category' => 'Berita']);

        Schema::dropIfExists('early_warning_sources');
    }
};
