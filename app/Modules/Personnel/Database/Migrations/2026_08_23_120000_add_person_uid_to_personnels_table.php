<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Stable person identity that survives renumbering, deletion and re-hire.
 *
 * ## Why `tabel_no` cannot do this job
 *
 * The staff number is the right internal key: every domain table foreign-keys it
 * with `cascadeOnUpdate`, so a correction sweeps the whole database at once and a
 * deleted person restored under the same number keeps their history. That design
 * stays exactly as it is.
 *
 * What it cannot do is identify a *person* to a system that is not part of that
 * cascade. Two cases break it:
 *
 * 1. **Renumbering.** The old value survives nowhere, so the counterpart system
 *    sees a brand-new employee.
 * 2. **Re-hire.** The same human comes back as a new `personnels` row with a new
 *    number.
 *
 * In both cases the payroll side computes income tax on a *cumulative* yearly
 * base. Split that base in two and the employee is taxed at a lower bracket —
 * no error surfaces, the figures are simply wrong.
 *
 * `person_uid` is issued once and never changes. `person_registry` keeps the
 * number → identity mapping so that a hard-deleted person recreated under the
 * same staff number is reunited with their own identity rather than given a new
 * one — the very behaviour the `tabel_no` design was chosen for, extended across
 * the system boundary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table): void {
            $table->char('person_uid', 36)->nullable()->after('tabel_no')->index();
        });

        Schema::create('person_registry', function (Blueprint $table): void {
            $table->id();
            $table->string('tabel_no')->unique();
            $table->char('person_uid', 36)->index();
            $table->timestamps();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('person_registry');

        Schema::table('personnels', function (Blueprint $table): void {
            $table->dropIndex(['person_uid']);
            $table->dropColumn('person_uid');
        });
    }

    /**
     * Give every existing person an identity and record it.
     *
     * Soft-deleted rows are included on purpose: they can be restored, and their
     * history is still referenced by payroll.
     */
    private function backfill(): void
    {
        DB::table('personnels')
            ->orderBy('id')
            ->select(['id', 'tabel_no'])
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    $uid = (string) Str::uuid();

                    DB::table('personnels')->where('id', $row->id)->update(['person_uid' => $uid]);

                    $tabelNo = trim((string) $row->tabel_no);

                    if ($tabelNo === '') {
                        continue;
                    }

                    DB::table('person_registry')->updateOrInsert(
                        ['tabel_no' => $tabelNo],
                        ['person_uid' => $uid, 'created_at' => now(), 'updated_at' => now()],
                    );
                }
            });
    }
};
