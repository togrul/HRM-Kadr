<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\Personnel;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Integration\Support\Contract;

/**
 * What each employee is owed — as **conditions**, not as a calculation.
 *
 * ## The distinction this feed exists to preserve
 *
 * HR decides who is employed, on what base pay, and with which allowances. Those
 * are conditions. Turning them into a net figure needs progressive tax brackets,
 * social-insurance rates by sector, average-earnings rules, garnishment ceilings
 * and the accounting period the result posts into — none of which lives here.
 *
 * So the feed carries the conditions and stops. The payroll side applies the law
 * to them.
 *
 * ## Statutory components never cross
 *
 * The component catalogue here also holds income tax, pension and the rest,
 * flagged `is_statutory`. Those are **excluded**. Sending them would mean two
 * systems each holding a rate table for the same tax, and when the law changed
 * one of them would quietly be wrong. The payroll side computes them from its
 * own tables, which is where the legal engine actually is.
 */
class CompensationFeedService
{
    public function __construct(private readonly CompensationReadRepository $compensation) {}

    /**
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}
     */
    public function page(int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $people = Personnel::query()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'tabel_no', 'person_uid']);

        $hasMore = $people->count() > $limit;
        $people = $people->take($limit);

        return [
            // `row()` null qaytara bilir — kompensasiyası olmayan şəxsin
            // göndəriləcək şərti yoxdur. Closure-un dönüş tipi bunu göstərməlidir.
            'items' => $people->map(fn (Personnel $person): ?array => $this->row($person))
                ->filter()
                ->values()
                ->all(),
            'last_sequence' => (int) ($people->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }

    /** @return array<string, mixed>|null */
    private function row(Personnel $person): ?array
    {
        $tabelNo = trim((string) $person->tabel_no);

        if ($tabelNo === '') {
            return null;
        }

        $current = $this->compensation->currentCompensation($tabelNo);

        if ($current === null) {
            return null;
        }

        return [
            'external_id' => (string) $person->id,
            'person_uid' => (string) $person->person_uid,
            'external_no' => $tabelNo,
            'base_amount' => (float) $current->base_amount,
            'currency' => (string) ($current->currency ?: 'AZN'),
            'effective_from' => $this->date($current->effective_from),
            'effective_to' => $this->date($current->effective_to),
            'components' => $this->components($tabelNo),
        ];
    }

    /**
     * The allowance and deduction lines HR has decided on.
     *
     * Statutory lines are dropped here rather than at the far end: what never
     * leaves cannot be misread, and the exclusion stays visible next to the
     * reason for it.
     *
     * @return list<array<string, mixed>>
     */
    private function components(string $tabelNo): array
    {
        return $this->compensation->componentsFor($tabelNo)
            ->reject(fn (array $line): bool => (bool) ($line['is_statutory'] ?? false))
            ->map(fn (array $line): array => [
                'code' => (string) ($line['component_code'] ?? ''),
                'name' => (string) ($line['component_name'] ?? ''),
                'type' => (string) ($line['type'] ?? 'earning'),
                'amount' => (float) ($line['amount'] ?? 0),
                // Passed as HR's intent, not as an instruction: the payroll side
                // decides what its own tax rules make of them.
                'taxable' => (bool) ($line['taxable'] ?? true),
                'affects_social' => (bool) ($line['affects_social'] ?? true),
            ])
            ->values()
            ->all();
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_object($value) && method_exists($value, 'format')
            ? $value->format('Y-m-d')
            : substr((string) $value, 0, 10);
    }
}
