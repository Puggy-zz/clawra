<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_models', function (Blueprint $table) {
            $table->unsignedInteger('active_requests')->default(0)->after('is_default');
            $table->unsignedInteger('max_concurrent_requests')->default(1)->after('active_requests');
        });
    }

    public function down(): void
    {
        Schema::table('provider_models', function (Blueprint $table) {
            $table->dropColumn(['active_requests', 'max_concurrent_requests']);
        });
    }
};
