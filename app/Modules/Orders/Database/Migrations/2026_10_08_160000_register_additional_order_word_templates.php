<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs that already run the standard order catalogue (orders:seed-word-templates)
 * get the order types added since: business trip, maternity leave, disciplinary sanction,
 * substitution and salary change. Idempotent — a code the install already has (possibly
 * edited in the designer) is left alone. A fresh install with no templates is untouched;
 * running the seeder there registers the whole catalogue, these included.
 */
return new class extends Migration
{
    private const CODES = ['ezamiyyet', 'analiq_mezuniyyeti', 'intizam_tenbehi', 'evezetme', 'emek_haqqi_deyisme'];

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
