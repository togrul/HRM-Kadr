<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The "passport" requirement is now met by a passport or an identity card (şəxsiyyət
 * vəsiqəsi), so its label names the identity document instead of the passport alone.
 * Only the seeded labels are replaced; a label someone already changed is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->relabel(
            from: ['az' => 'Pasport', 'en' => 'Passport'],
            to: ['az' => 'Şəxsiyyət sənədi (pasport və ya ŞV)', 'en' => 'Identity document (passport or ID card)'],
        );
    }

    public function down(): void
    {
        $this->relabel(
            from: ['az' => 'Şəxsiyyət sənədi (pasport və ya ŞV)', 'en' => 'Identity document (passport or ID card)'],
            to: ['az' => 'Pasport', 'en' => 'Passport'],
        );
    }

    /**
     * @param  array{az: string, en: string}  $from
     * @param  array{az: string, en: string}  $to
     */
    private function relabel(array $from, array $to): void
    {
        if (! Schema::hasTable('compliance_document_requirements')) {
            return;
        }

        DB::table('compliance_document_requirements')
            ->where('key', 'passport')
            ->where('label_az', $from['az'])
            ->update(['label_az' => $to['az'], 'updated_at' => now()]);

        DB::table('compliance_document_requirements')
            ->where('key', 'passport')
            ->where('label_en', $from['en'])
            ->update(['label_en' => $to['en'], 'updated_at' => now()]);
    }
};
