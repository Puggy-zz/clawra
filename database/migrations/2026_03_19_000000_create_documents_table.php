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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('file_path')->nullable(); // Path to markdown file
            $table->string('file_name'); // Original filename
            $table->string('file_type')->default('md'); // md, txt, etc.
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->string('access_level')->default('project'); // project, global, private
            $table->json('metadata')->nullable(); // For tags, categories, etc.
            $table->timestamps();

            $table->index(['project_id', 'access_level']);
            $table->index(['task_id']);
            $table->index(['file_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
