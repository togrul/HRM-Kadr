<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_dispatches', function (Blueprint $table) {
            $table->unsignedInteger('attempt_count')->default(0)->after('status');
            $table->timestamp('last_attempt_at')->nullable()->after('attempt_count');
            $table->json('meta')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('notification_dispatches', function (Blueprint $table) {
            $table->dropColumn(['attempt_count', 'last_attempt_at', 'meta']);
        });
    }
};
