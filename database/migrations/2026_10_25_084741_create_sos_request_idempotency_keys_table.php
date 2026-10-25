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
        if (! Schema::hasTable('sos_request_idempotency_keys')) {
            Schema::create('sos_request_idempotency_keys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->uuid('client_request_id');
                $table->foreignId('sos_request_id')
                    ->nullable()
                    ->constrained('sos_requests')
                    ->cascadeOnDelete();
                $table->unique(['user_id', 'client_request_id']);
                $table->unique('sos_request_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve idempotency records during rollback.
    }
};
