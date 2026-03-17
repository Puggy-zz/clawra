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
        Schema::create('provider_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_route_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('external_name')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('config')->nullable();
            $table->unsignedInteger('context_window')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['provider_route_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_models');
    }
};
