<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Staff number → person identity.
 *
 * The staff number is deliberately reusable: delete a person, recreate them
 * under the same number, and their records reattach. That is why the domain
 * tables key on it.
 *
 * This registry extends the same guarantee to the identity itself. Without it a
 * recreated person would receive a *new* `person_uid`, and the counterpart
 * system would treat them as somebody else — splitting the cumulative yearly
 * income-tax base in two without any visible error.
 */
class PersonRegistry extends Model
{
    protected $table = 'person_registry';

    protected $guarded = ['id'];

    /**
     * The identity that belongs to this staff number, minting one on first use.
     *
     * Called on every hire, so it must be cheap and idempotent.
     */
    public static function identityFor(?string $tabelNo): string
    {
        $tabelNo = trim((string) $tabelNo);

        if ($tabelNo === '') {
            // No number yet (pending candidate): a free-standing identity is
            // still correct — it simply is not reusable by number.
            return (string) Str::uuid();
        }

        $existing = static::query()->where('tabel_no', $tabelNo)->value('person_uid');

        if ($existing) {
            return (string) $existing;
        }

        $uid = (string) Str::uuid();

        static::query()->updateOrCreate(['tabel_no' => $tabelNo], ['person_uid' => $uid]);

        return $uid;
    }

    /**
     * Keep the registry pointing at the number a person currently holds.
     *
     * The identity never changes; only which number resolves to it. Renumbering
     * therefore adds a row rather than rewriting one — the old number keeps
     * resolving to the same person, which is what makes historical records
     * readable.
     */
    public static function remember(string $tabelNo, string $personUid): void
    {
        $tabelNo = trim($tabelNo);

        if ($tabelNo === '') {
            return;
        }

        static::query()->updateOrCreate(['tabel_no' => $tabelNo], ['person_uid' => $personUid]);
    }
}
