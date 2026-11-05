<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['sos_requests', 'sos_request_idempotency_keys'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $maxId = DB::table($tableName)->max('id');
            if ($maxId === null) {
                continue;
            }

            $nextId = (int) $maxId + 1;
            $tableMetadata = DB::selectOne(
                'SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$tableName],
            );

            if ($tableMetadata !== null && (int) $tableMetadata->next_id < $nextId) {
                DB::statement('ALTER TABLE `'.$tableName.'` AUTO_INCREMENT = '.$nextId);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Auto-increment counters are intentionally not lowered during rollback.
    }
};
