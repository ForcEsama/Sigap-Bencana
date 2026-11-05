<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('news', 'is_simulation')) {
            Schema::table('news', function (Blueprint $table): void {
                $table->boolean('is_simulation')->default(false)->after('category');
            });
        }

        DB::table('news')
            ->where(function ($query): void {
                $query->where('title', 'like', 'Simulasi Berita:%')
                    ->orWhere('source', 'like', 'Contoh konten%');
            })
            ->update(['is_simulation' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('news', 'is_simulation')) {
            Schema::table('news', function (Blueprint $table): void {
                $table->dropColumn('is_simulation');
            });
        }
    }
};
