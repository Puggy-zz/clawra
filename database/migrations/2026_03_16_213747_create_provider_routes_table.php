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
        Schema::create('provider_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('harness');
            $table->string('auth_mode');
            $table->string('credential_type')->nullable();
            $table->json('usage_snapshot')->nullable();
            $table->json('rate_limits')->nullable();
            $table->json('capability_tags')->nullable();
            $table->json('config')->nullable();
            $table->boolean('supports_tools')->default(false);
            $table->boolean('supports_structured_output')->default(false);
            $table->integer('priority')->default(100);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['provider_id', 'harness', 'auth_mode'], 'provider_routes_identity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_routes');
    }
};
