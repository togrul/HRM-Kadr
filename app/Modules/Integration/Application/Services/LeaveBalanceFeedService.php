<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\Personnel;
use App\Modules\Integration\Support\Contract;
use App\Services\Vacation\VacationBalanceService;

/**
 * Each employee's annual leave balance.
 *
 * ## Why payroll needs this
 *
 * On dismissal the employee is paid for leave they earned and did not take
 * (Labour Code art. 144). That figure is a *payment*, so the finance side
 * computes it — but the day count is an HR fact, recorded here as leave is
 * approved and taken. Without it the final settlement is guesswork.
 *
 * It is a balance, not a history: the counterpart needs to know how many days
 * remain, not which leave records produced them.
 */
class LeaveBalanceFeedService
{
    public function __construct(private readonly VacationBalanceService $balances) {}

    /**
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool, year: int}
     */
    public function page(int $year, int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $people = Personnel::query()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $people->count() > $limit;
        $people = $people->take($limit);

        return [
            'year' => $year,
            'items' => $people->map(function (Personnel $person) use ($year): array {
                $snapshot = $this->balances->snapshot($person, $year);

                return [
                    'external_id' => (string) $person->id,
                    'person_uid' => (string) $person->person_uid,
                    'external_no' => trim((string) $person->tabel_no) ?: null,
                    'year' => $year,
                    'entitled_days' => (int) $snapshot['total'],
                    'used_days' => (int) $snapshot['used'],
                    'remaining_days' => (int) $snapshot['remaining'],
                ];
            })->values()->all(),
            'last_sequence' => (int) ($people->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }
}
