<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use Illuminate\Support\Carbon;

/**
 * Mərhələ SLA-sı: hər mərhələnin son tarixi `config('employee_relations.sla_days')`-dən,
 * «araşdırılır» üçün iş növünün öz müddəti varsa ondan hesablanır.
 */
class CaseSla
{
    public function days(RelationCase $case, CaseStatus $status): ?int
    {
        if (! $status->hasSla()) {
            return null;
        }

        $days = (int) config('employee_relations.sla_days.'.$status->value, 0);

        if ($status === CaseStatus::Investigating) {
            $typeDays = (int) ($case->type->investigation_sla_days ?? 0);
            $days = $typeDays > 0 ? $typeDays : $days;
        }

        return $days > 0 ? $days : null;
    }

    public function dueAt(RelationCase $case, CaseStatus $status, Carbon $from): ?Carbon
    {
        $days = $this->days($case, $status);

        return $days === null ? null : $from->copy()->addDays($days);
    }
}
