<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\Personnel;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Integration\Support\Contract;
use Illuminate\Support\Collection;

/**
 * The employee feed served to the finance system.
 *
 * ## Cursor, not page number
 *
 * Rows are ordered by `personnels.id` and the caller passes the last id it has
 * seen. A page number would shift under the caller's feet as people are hired,
 * silently skipping rows; an id cursor cannot.
 *
 * ## The field list is a whitelist, not a convenience
 *
 * {@see self::row()} names every field explicitly and never serialises the
 * model. The personnel record accumulates disciplinary notes, medical results
 * and war-participation flags; a `toArray()` would push all of it across the
 * boundary the moment somebody added a column, and nobody would notice.
 *
 * What crosses is what payroll needs to compute and report — nothing else.
 */
class EmployeeFeedService
{
    public function __construct(private readonly CompensationReadRepository $compensation) {}

    /**
     * One page of the feed.
     *
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}
     */
    public function page(int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $people = Personnel::query()
            ->with(['structure:id,code,name', 'position:id,name'])
            ->where('id', '>', $after)
            ->orderBy('id')
            // One extra row answers "is there more?" without a second COUNT
            // over a table that grows with every hire.
            ->limit($limit + 1)
            ->get();

        $hasMore = $people->count() > $limit;
        $people = $people->take($limit);

        $salaries = $this->salariesFor($people);

        return [
            'items' => $people->map(fn (Personnel $p) => $this->row($p, $salaries))->values()->all(),
            'last_sequence' => (int) ($people->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }

    public function total(): int
    {
        return Personnel::query()->count();
    }

    /**
     * One employee, whitelisted.
     *
     * @param  Collection<string, float>  $salaries
     * @return array<string, mixed>
     */
    private function row(Personnel $person, Collection $salaries): array
    {
        $tabelNo = trim((string) $person->tabel_no);

        return [
            // Our internal key. The counterpart correlates on this, never on the
            // staff number: that one is editable and cascades, leaving no trace
            // of its previous value.
            'external_id' => (string) $person->id,
            'person_uid' => (string) $person->person_uid,
            'external_no' => $tabelNo !== '' ? $tabelNo : null,

            'last_name' => (string) $person->surname,
            'first_name' => (string) $person->name,
            'patronymic' => $this->text($person->patronymic),
            'birth_date' => $this->date($person->birthdate),
            'gender' => $this->text($person->gender),
            // Tax ID: needed for statutory reporting on the finance side. Empty
            // for foreign nationals, which is why it is a cross-check and never
            // a join key.
            'fin' => $this->text($person->pin),
            'phone' => $this->text($person->mobile ?: $person->phone),
            'email' => $this->text($person->email),

            'department_code' => $this->text($person->structure?->code) ?? $this->text($person->structure_id),
            'position_code' => $this->text($person->position_id),
            'grade' => 0,
            'category_code' => null,

            'hire_date' => $this->date($person->join_work_date),
            'dismiss_date' => $this->date($person->leave_work_date),
            'status' => $person->leave_work_date ? 'dismissed' : 'active',
            'base_salary' => (float) ($salaries[$tabelNo] ?? 0.0),

            'work_schedule_code' => $this->text($person->work_norm_id),
        ];
    }

    /**
     * Current base pay per staff number — one query for the whole page.
     *
     * Read through the Compensation module's contract rather than its tables:
     * the effective-dated selection lives there and reimplementing it here would
     * let the two drift apart, with the feed quietly reporting a superseded
     * salary.
     *
     * @param  Collection<int, Personnel>  $people
     * @return Collection<string, float>
     */
    private function salariesFor(Collection $people): Collection
    {
        $tabelNos = $people
            ->map(fn (Personnel $p) => trim((string) $p->tabel_no))
            ->filter(fn (string $no) => $no !== '')
            ->unique()
            ->values()
            ->all();

        return $this->compensation->baseAmountsFor($tabelNos);
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_object($value) && method_exists($value, 'format')) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }
}
