<?php

use App\Modules\Orders\Infrastructure\Document\VacationOrderTemplateUpgrader;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Annual leave orders name the work year they are issued for (the "İş ili" field now feeds
 * the work-year ledger), and the unused-leave compensation order is worded for the
 * termination of the employment contract (ƏM m.144.2). Unedited templates only;
 * idempotent — see VacationOrderTemplateUpgrader.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates') || ! DB::table('order_word_templates')->exists()) {
            return;
        }

        Log::info('orders.word_templates.vacation_upgrade', app(VacationOrderTemplateUpgrader::class)->run());
    }

    public function down(): void
    {
        // The previous masters are archived as template versions; nothing to undo here.
    }
};
