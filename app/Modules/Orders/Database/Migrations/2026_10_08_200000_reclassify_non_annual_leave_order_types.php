<?php

use App\Modules\Orders\Infrastructure\Document\NonAnnualLeaveReclassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Paternity (ƏM m.125.4), education (m.123) and unpaid leave (m.128–130) are leave kinds
 * of their own and never consume the annual labour leave (m.112.1). Installs whose
 * standard catalogue still runs them on the annual 'vacation' effect are moved to the
 * non-deducting effects, and the days their approved orders took from the stored yearly
 * balance are given back. Idempotent — see NonAnnualLeaveReclassifier; the same step is
 * available as `orders:reclassify-non-annual-leave [--dry-run]`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates') || ! Schema::hasTable('order_logs')) {
            return;
        }

        app(NonAnnualLeaveReclassifier::class)->run();
    }

    public function down(): void
    {
        // The balance corrections reflect the law; putting the deductions back would not.
    }
};
