<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('trigger')->constrained('notification_templates')->nullOnDelete();
            $table->string('channel')->default('database')->after('title');
            $table->json('audience_config')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn(['channel', 'audience_config']);
        });
    }
};
