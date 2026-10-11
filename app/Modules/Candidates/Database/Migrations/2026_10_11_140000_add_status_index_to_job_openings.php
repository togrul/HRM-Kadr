<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR Command Center «açıq vakansiyalar» paneli status + yaş üzrə oxuyur; mövcud
 * (profile_pack, status) indeksi status ilə başlamadığı üçün ayrıca indeks.
 */
return new class extends Migration
{
    private const INDEX = 'job_openings_status_created_idx';

    public function up(): void
    {
        if (! Schema::hasTable('job_openings') || Schema::hasIndex('job_openings', self::INDEX)) {
            return;
        }

        Schema::table('job_openings', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_openings') || ! Schema::hasIndex('job_openings', self::INDEX)) {
            return;
        }

        Schema::table('job_openings', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX);
        });
    }
};
