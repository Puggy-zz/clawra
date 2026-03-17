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
        Schema::table('agents', function (Blueprint $table) {
            $table->string('status')->default('active')->after('description');
            $table->json('execution_preferences')->nullable()->after('tools');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('vendor')->nullable()->after('name');
            $table->json('config')->nullable()->after('priority_preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['status', 'execution_preferences']);
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['vendor', 'config']);
        });
    }
};
