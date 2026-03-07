<?php

declare(strict_types=1);

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
        Schema::create('heartbeat_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp'); // Heartbeat timestamp
            $table->json('decisions'); // Decisions made during heartbeat
            $table->json('tasks_queued'); // Tasks queued during heartbeat
            $table->json('provider_status'); // Provider status snapshot
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heartbeat_logs');
    }
};
