<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sandboxes', function (Blueprint $table) {
            $table->foreignId('task_id')->nullable()->after('project_id')
                ->constrained()->nullOnDelete();
            $table->string('sandbox_id')->nullable()->after('task_id');
            $table->string('image')->nullable()->after('sandbox_id');
        });
    }

    public function down(): void
    {
        Schema::table('sandboxes', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn(['task_id', 'sandbox_id', 'image']);
        });
    }
};
