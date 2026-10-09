<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelPunishment;
use App\Models\Punishment;
use App\Models\PunishmentType;
use App\Modules\Orders\Infrastructure\Document\DisciplinarySanctionTerm;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\Carbon;

/**
 * Imposes a disciplinary sanction (intizam tənbehi): records it in the employee's file
 * with the sanction type and the violation, dated the sanction day and expiring after
 * the configured term (Admin → Settings, 12 months by default). The daily
 * `personnel:lift-expired-sanctions` run marks it lifted once that date passes.
 * Reversal removes the record.
 */
class DisciplinaryEffect implements OrderEffect
{
    use RemembersEffectState;

    /** Catalogue entry used when the violation matches none of the catalogue's names. Data key. */
    public const GENERIC_VIOLATION = 'Əmək intizamını pozduğuna görə';

    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly DisciplinarySanctionTerm $term,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        if (blank($personnel->tabel_no)) {
            return;
        }

        $given = $this->dates->parse($fields['date'] ?? null)
            ?? ($order->given_date ? Carbon::parse($order->given_date) : today());
        $given = Carbon::parse($given->format('Y-m-d'));

        $sanction = trim((string) ($fields['sanction_type'] ?? ''));
        $violation = trim((string) ($fields['violation'] ?? ''));

        $record = PersonnelPunishment::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'punishment_id' => $this->punishmentId($violation),
            'reason' => trim($sanction.($sanction !== '' && $violation !== '' ? ' — ' : '').$violation) ?: self::GENERIC_VIOLATION,
            'given_date' => $given->toDateString(),
            'expired_date' => $given->copy()->addMonthsNoOverflow($this->term->months())->toDateString(),
            'order_no' => $order->order_no,
            'order_given_by' => (string) ($order->given_by ?? ''),
            'order_date' => optional($order->given_date)->format('Y-m-d'),
        ]);

        $this->rememberState($order, ['punishment_record_id' => (int) $record->id]);
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $recordId = (int) ($this->rememberedState($order)['punishment_record_id'] ?? 0);

        PersonnelPunishment::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->when(
                $recordId > 0,
                fn ($query) => $query->whereKey($recordId),
                fn ($query) => $query->where('order_no', $order->order_no),
            )
            ->delete();

        $this->forgetState($order, ['punishment_record_id']);
    }

    /**
     * The "other" (non-criminal) catalogue entry named like the violation, or the
     * generic disciplinary entry — created on first use, since the record needs one.
     */
    private function punishmentId(string $violation): int
    {
        $type = Punishment::PUNISHMENT_TYPES['other'];

        $id = $violation !== ''
            ? Punishment::query()->where('punishment_type_id', $type)->where('name', $violation)->value('id')
            : null;

        $id ??= Punishment::query()->where('punishment_type_id', $type)->where('name', self::GENERIC_VIOLATION)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        PunishmentType::query()->firstOrCreate(['id' => $type], ['name' => 'digər']);

        return (int) Punishment::query()->create([
            'id' => (int) Punishment::query()->max('id') + 1,
            'punishment_type_id' => $type,
            'name' => self::GENERIC_VIOLATION,
        ])->id;
    }
}
