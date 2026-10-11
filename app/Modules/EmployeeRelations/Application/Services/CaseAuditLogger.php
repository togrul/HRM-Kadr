<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;

/**
 * İşçi münasibətləri əməliyyatlarını activity log-a (`employee_relations`) yazır.
 *
 * Xüsusiyyətlərə yalnız iş nömrəsi, mərhələ və texniki id-lər düşür: şikayətin məzmunu,
 * şəxslərin adları və anonim şikayətçinin kimliyi audit jurnalına yazılmır. Anonim
 * müraciətdə səbəbkar (causer) da boş saxlanılır — əks halda jurnal kimliyi açardı.
 */
class CaseAuditLogger
{
    public const LOG_NAME = 'employee_relations';

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, RelationCase $case, array $properties = [], ?User $causer = null, bool $anonymous = false): void
    {
        if (! function_exists('activity')) {
            return;
        }

        $logger = activity(self::LOG_NAME)
            ->event($event)
            ->performedOn($case)
            ->withProperties(['case_number' => $case->case_number, ...$properties]);

        if ($anonymous) {
            $logger->causedByAnonymous();
        } elseif ($causer !== null) {
            $logger->causedBy($causer);
        }

        $logger->log('employee_relations.'.$event);
    }
}
