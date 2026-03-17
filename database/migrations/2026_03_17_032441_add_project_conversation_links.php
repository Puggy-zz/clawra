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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_conversation_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });

        Schema::table('process_logs', function (Blueprint $table) {
            $table->foreignId('project_conversation_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });

        Schema::table('external_sessions', function (Blueprint $table) {
            $table->foreignId('project_conversation_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_conversation_id');
        });

        Schema::table('process_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_conversation_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_conversation_id');
        });
    }
};
