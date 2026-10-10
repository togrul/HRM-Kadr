<?php

namespace App\Modules\Integration\Infrastructure;

use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Gives committed outbox rows their feed sequence, in commit order.
 *
 * An auto-increment id is taken when the row is written, but the row becomes visible only
 * when its (possibly long) transaction commits — so on MySQL/PostgreSQL a lower id can
 * appear after a reader has already moved its cursor past it, and the event is skipped for
 * good. The sequence is assigned instead after the writing transaction committed, under a
 * row lock on a single-row counter: assigners run one after another and each commits its
 * numbers before the next one starts, so a reader never sees N+1 without N.
 *
 * Rows a crashed process left without a sequence are picked up by the next assignment
 * (every write and every feed read runs one), still in id order.
 */
class OutboxSequencer
{
    private const BATCH = 1000;

    public function assignPending(): void
    {
        if (! InstalledTables::has('integration_outbox_sequence')) {
            return;
        }

        DB::transaction(function (): void {
            $counter = DB::table('integration_outbox_sequence')->where('id', 1)->lockForUpdate()->first();

            if ($counter === null) {
                DB::table('integration_outbox_sequence')->insertOrIgnore(['id' => 1, 'last_value' => (int) DB::table('integration_outbox')->max('sequence')]);
                $counter = DB::table('integration_outbox_sequence')->where('id', 1)->lockForUpdate()->first();
            }

            $pending = DB::table('integration_outbox')
                ->whereNull('sequence')
                ->orderBy('id')
                ->limit(self::BATCH)
                ->lockForUpdate()
                ->pluck('id');

            if ($pending->isEmpty()) {
                return;
            }

            $next = (int) $counter->last_value;
            foreach ($pending as $id) {
                DB::table('integration_outbox')->where('id', $id)->whereNull('sequence')->update(['sequence' => ++$next]);
            }

            DB::table('integration_outbox_sequence')->where('id', 1)->update(['last_value' => $next]);
        });
    }
}
