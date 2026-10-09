<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Models\PersonnelKinship;
use App\Models\PersonnelLaborActivity;
use App\Modules\Personnel\Contracts\LeaveEntitlementFacts;
use App\Modules\Personnel\Contracts\LeaveEntitlementFactsProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * İşçi kartından məzuniyyət faktlarını yığır (üç sorğu, paket üçün):
 *   - işçi: cins, doğum tarixi, işə qəbul / xitam tarixi, vəzifə, əlillik və onun tarixi;
 *   - ailə üzvləri: «Oğul» / «Qız» qohumluğu olanlar uşaq sayılır (doğum tarixi, əlillik qeydi);
 *   - əmək fəaliyyəti: cari iş qeydi xaricindəki dövrlər (ümumi əmək stajı, ƏM m.116 — staj
 *     bütün işəgötürənlər üzrə sayılır).
 */
class LeaveEntitlementFactsService implements LeaveEntitlementFactsProvider
{
    /** Qohumluq kataloqunda uşaq qeydləri (standart kataloq: 23 — Oğul, 24 — Qız). */
    public const CHILD_KINSHIP_IDS = [23, 24];

    public const CHILD_KINSHIP_NAMES = ['oğul', 'qız', 'oğlu', 'qızı', 'övlad'];

    public function facts(array $tabelNos): array
    {
        $tabelNos = array_values(array_unique(array_filter(array_map('strval', $tabelNos), fn (string $no): bool => $no !== '')));

        if ($tabelNos === []) {
            return [];
        }

        $people = Personnel::query()
            ->withTrashed()
            ->whereIn('tabel_no', $tabelNos)
            ->toBase()
            ->get(['tabel_no', 'gender', 'birthdate', 'join_work_date', 'leave_work_date', 'position_id', 'disability_id', 'disability_given_date']);

        $children = $this->children($tabelNos);
        $employment = $this->previousEmployment($tabelNos);
        $facts = [];

        foreach ($people as $row) {
            $tabelNo = (string) $row->tabel_no;

            $facts[$tabelNo] = new LeaveEntitlementFacts(
                tabelNo: $tabelNo,
                gender: $row->gender !== null ? (int) $row->gender : null,
                birthdate: $this->date($row->birthdate),
                joinDate: $this->date($row->join_work_date),
                leaveDate: $this->date($row->leave_work_date),
                positionId: $row->position_id !== null ? (int) $row->position_id : null,
                disabled: filled($row->disability_id),
                disabledSince: $this->date($row->disability_given_date),
                children: $children[$tabelNo] ?? [],
                previousEmployment: $employment[$tabelNo] ?? [],
            );
        }

        return $facts;
    }

    /**
     * @param  list<string>  $tabelNos
     * @return array<string, list<array{birthdate: ?CarbonImmutable, disabled: bool}>>
     */
    private function children(array $tabelNos): array
    {
        $columns = ['personnel_kinships.tabel_no', 'personnel_kinships.kinship_id', 'personnel_kinships.birthdate', 'kinships.name_az'];
        $query = fn (array $columns) => PersonnelKinship::query()
            ->leftJoin('kinships', 'kinships.id', '=', 'personnel_kinships.kinship_id')
            ->whereIn('personnel_kinships.tabel_no', $tabelNos)
            ->toBase()
            ->get($columns);

        try {
            $rows = $query([...$columns, 'personnel_kinships.is_disabled']);
        } catch (QueryException) {
            // Əlillik sütunu hələ miqrasiya olunmayıbsa: uşaq sayı yenə də tətbiq olunur.
            $rows = $query($columns);
        }

        $children = [];

        foreach ($rows as $row) {
            $isChild = in_array((int) $row->kinship_id, self::CHILD_KINSHIP_IDS, true)
                || in_array(mb_strtolower(trim((string) $row->name_az)), self::CHILD_KINSHIP_NAMES, true);

            if (! $isChild) {
                continue;
            }

            $children[(string) $row->tabel_no][] = [
                'birthdate' => $this->date($row->birthdate),
                'disabled' => (bool) ($row->is_disabled ?? false),
            ];
        }

        return $children;
    }

    /**
     * @param  list<string>  $tabelNos
     * @return array<string, list<array{0: CarbonImmutable, 1: ?CarbonImmutable}>>
     */
    private function previousEmployment(array $tabelNos): array
    {
        $rows = PersonnelLaborActivity::query()
            ->whereIn('tabel_no', $tabelNos)
            ->toBase()
            ->get(['tabel_no', 'join_date', 'leave_date', 'is_current']);

        $intervals = [];

        foreach ($rows as $row) {
            // Cari iş qeydi bu işəgötürəndəki işdir — o, işə qəbul tarixindən ayrıca sayılır.
            if ((bool) $row->is_current && blank($row->leave_date)) {
                continue;
            }

            $start = $this->date($row->join_date);

            if ($start === null) {
                continue;
            }

            $intervals[(string) $row->tabel_no][] = [$start, $this->date($row->leave_date)];
        }

        return $intervals;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
