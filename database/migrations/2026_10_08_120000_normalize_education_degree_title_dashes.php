<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Education degree names used two separators ("Ali təhsil - 1993-cü ilə qədər" and
 * "Ali təhsil — 1997-ci ilə qədər"). Every spaced dash becomes " — ". Idempotent: a title
 * that is already normalized is not touched, so re-running changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('education_degrees')) {
            return;
        }

        DB::table('education_degrees')->orderBy('id')->get()->each(function (object $degree): void {
            $changes = [];

            foreach (['title_az', 'title_en', 'title_ru'] as $column) {
                $value = $degree->{$column} ?? null;
                if (! is_string($value)) {
                    continue;
                }

                $normalized = trim((string) preg_replace('/\s+[-–—]+\s+/u', ' — ', $value));
                if ($normalized !== $value) {
                    $changes[$column] = $normalized;
                }
            }

            if ($changes !== []) {
                DB::table('education_degrees')->where('id', $degree->id)->update($changes);
            }
        });
    }

    public function down(): void
    {
        // Typography only; the previous mixed separators are not worth restoring.
    }
};
