<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs that already run the standard order catalogue get the order types added
 * since: child-care social leave, honorary diploma, temporary assignment, leave
 * postponement, reduced working time, donor day, election commission, civil defence
 * training, recall from leave, unused-leave compensation, non-working-day work and order
 * revocation. Idempotent — a code the install already has (possibly edited in the
 * designer) is left alone. A fresh install with no templates is untouched; running the
 * seeder there registers the whole catalogue, these included.
 */
return new class extends Migration
{
    private const CODES = [
        'usaga_qulluq_mezuniyyeti',
        'fexri_ferman',
        'hevale',
        'mezuniyyetin_kecirilmesi',
        'qisaldilmis_is_vaxti',
        'donor_gunu',
        'secki_komissiyasi',
        'mulki_mudafie',
        'mezuniyyetden_geri_cagirma',
        'istifade_olunmamis_mezuniyyet_kompensasiyasi',
        'qeyri_is_gunu_ise_celb',
        'emrin_legvi',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates') || ! DB::table('order_word_templates')->exists()) {
            return;
        }

        foreach (self::CODES as $code) {
            if (! DB::table('order_word_templates')->where('code', $code)->exists()) {
                Artisan::call('orders:seed-word-templates', ['--only' => $code]);
            }
        }
    }

    public function down(): void
    {
        // Issued orders keep referring to these types; removing them would orphan their snapshots.
    }
};
