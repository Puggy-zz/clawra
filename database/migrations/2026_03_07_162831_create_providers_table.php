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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // API-key-based / CLI-tool-based
            $table->string('api_protocol'); // OpenAI-compatible / Anthropic-compatible / native
            $table->json('usage_snapshot')->nullable(); // Current usage snapshot
            $table->json('rate_limits')->nullable(); // Rate limit structure
            $table->json('capability_tags')->nullable(); // Capability tags
            $table->json('priority_preferences')->nullable(); // Priority/preference rank for each capability tag
            $table->string('status')->default('active'); // active, rate-limited, degraded, disabled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
