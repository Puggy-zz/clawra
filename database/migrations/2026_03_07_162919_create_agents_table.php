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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Agent name (Planner, Developer, Test Writer, etc.)
            $table->string('role'); // Agent role
            $table->text('description')->nullable(); // Agent description
            $table->string('model'); // Default model assignment
            $table->string('fallback_model')->nullable(); // Fallback model
            $table->json('tools')->nullable(); // Available tools
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
