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
        Schema::create('agent_runtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_model_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fallback_provider_route_id')->nullable()->constrained('provider_routes')->nullOnDelete();
            $table->foreignId('fallback_provider_model_id')->nullable()->constrained('provider_models')->nullOnDelete();
            $table->string('name');
            $table->string('harness');
            $table->string('runtime_type');
            $table->string('runtime_ref');
            $table->text('description')->nullable();
            $table->json('tools')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['agent_id', 'harness', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_runtimes');
    }
};
