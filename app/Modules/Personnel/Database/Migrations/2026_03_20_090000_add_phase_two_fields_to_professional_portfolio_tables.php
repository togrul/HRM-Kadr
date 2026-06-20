<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_event_records', function (Blueprint $table) {
            $table->string('registry_key')->nullable()->after('source_url');
            $table->index('registry_key');
        });

        Schema::table('personnel_media_mentions', function (Blueprint $table) {
            $table->string('publisher_registry_key')->nullable()->after('url');
            $table->string('link_check_status')->nullable()->after('verification_status');
            $table->string('link_check_message')->nullable()->after('link_check_status');
            $table->unsignedSmallInteger('link_check_http_code')->nullable()->after('link_check_message');
            $table->timestamp('link_checked_at')->nullable()->after('link_check_http_code');
            $table->string('archive_health_status')->nullable()->after('link_checked_at');
            $table->string('archive_health_message')->nullable()->after('archive_health_status');
            $table->timestamp('archive_checked_at')->nullable()->after('archive_health_message');

            $table->index('publisher_registry_key');
            $table->index('link_check_status');
        });

        Schema::table('personnel_project_records', function (Blueprint $table) {
            $table->string('registry_key')->nullable()->after('reference_url');
            $table->index('registry_key');
        });
    }

    public function down(): void
    {
        Schema::table('personnel_project_records', function (Blueprint $table) {
            $table->dropIndex(['registry_key']);
            $table->dropColumn('registry_key');
        });

        Schema::table('personnel_media_mentions', function (Blueprint $table) {
            $table->dropIndex(['publisher_registry_key']);
            $table->dropIndex(['link_check_status']);
            $table->dropColumn([
                'publisher_registry_key',
                'link_check_status',
                'link_check_message',
                'link_check_http_code',
                'link_checked_at',
                'archive_health_status',
                'archive_health_message',
                'archive_checked_at',
            ]);
        });

        Schema::table('personnel_event_records', function (Blueprint $table) {
            $table->dropIndex(['registry_key']);
            $table->dropColumn('registry_key');
        });
    }
};
