<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('disaster_report_assignments')) {
            Schema::create('disaster_report_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('disaster_report_id');
                $table->foreignId('assigned_to')->nullable();
                $table->foreignId('assigned_by');
                $table->timestamps();
            });
        } else {
            Schema::table('disaster_report_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_to')->nullable()->change();
            });
        }

        $foreignKeys = collect(Schema::getForeignKeys('disaster_report_assignments'))
            ->pluck('name');

        Schema::table('disaster_report_assignments', function (Blueprint $table) use ($foreignKeys): void {
            if (! $foreignKeys->contains('disaster_report_assignments_disaster_report_id_foreign')) {
                $table->foreign('disaster_report_id')
                    ->references('id')
                    ->on('disaster_reports')
                    ->cascadeOnDelete();
            }

            if (! $foreignKeys->contains('disaster_report_assignments_assigned_to_foreign')) {
                $table->foreign('assigned_to')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! $foreignKeys->contains('disaster_report_assignments_assigned_by_foreign')) {
                $table->foreign('assigned_by')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            }
        });

        if (! Schema::hasIndex('disaster_report_assignments', 'disaster_report_assignments_disaster_report_id_unique')) {
            Schema::table('disaster_report_assignments', function (Blueprint $table): void {
                $table->unique('disaster_report_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep assignment history intact when rolling back application code.
    }
};
