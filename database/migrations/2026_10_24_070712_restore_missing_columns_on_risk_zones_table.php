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
        Schema::table('risk_zones', function (Blueprint $table) {
            if (! Schema::hasColumn('risk_zones', 'name')) {
                $table->string('name');
            }

            if (! Schema::hasColumn('risk_zones', 'disaster_type')) {
                $table->string('disaster_type', 30);
            }

            if (! Schema::hasColumn('risk_zones', 'risk_level')) {
                $table->string('risk_level', 20);
            }

            if (! Schema::hasColumn('risk_zones', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('risk_zones', 'region_name')) {
                $table->text('region_name');
            }

            if (! Schema::hasColumn('risk_zones', 'latitude')) {
                $table->decimal('latitude', 10, 7);
            }

            if (! Schema::hasColumn('risk_zones', 'longitude')) {
                $table->decimal('longitude', 10, 7);
            }

            if (! Schema::hasColumn('risk_zones', 'radius_meters')) {
                $table->unsignedInteger('radius_meters')->nullable();
            }

            if (! Schema::hasColumn('risk_zones', 'source')) {
                $table->string('source')->nullable();
            }

            if (! Schema::hasColumn('risk_zones', 'data_date')) {
                $table->date('data_date')->nullable();
            }

            if (! Schema::hasColumn('risk_zones', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (! Schema::hasIndex('risk_zones', ['disaster_type', 'risk_level'])) {
            Schema::table('risk_zones', function (Blueprint $table) {
                $table->index(['disaster_type', 'risk_level']);
            });
        }

        if (! Schema::hasIndex('risk_zones', ['is_active'])) {
            Schema::table('risk_zones', function (Blueprint $table) {
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve repaired columns so rollback cannot discard zone data.
    }
};
