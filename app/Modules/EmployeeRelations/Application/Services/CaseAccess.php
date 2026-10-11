<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * İşin məxfilik qaydası (yeganə mənbə).
 *
 * İşi yalnız bunlar görür, hamısı struktur görünürlüyü daxilində (StructureScope, fail closed):
 *  - `manage-employee-relations` icazəsi olan istifadəçi — görünən strukturların bütün işləri;
 *  - işə təyin olunmuş araşdırıcı;
 *  - işi açan istifadəçi (işin sahibi).
 * Struktursuz iş (məs. hələ işçiyə bağlanmamış anonim müraciət) yalnız «bütün strukturlar»
 * bayrağı olan istifadəçiyə görünür. Şəxsi kabinetin öz görünüşü MyCasesReadService-dədir.
 */
class CaseAccess
{
    public const SHOW = 'show-employee-relations';

    public const CREATE = 'create-employee-relations';

    public const MANAGE = 'manage-employee-relations';

    public const REVEAL = 'reveal-employee-relations-reporter';

    public const STATISTICS = 'show-employee-relations-statistics';

    public const SUBMIT_OWN = 'submit-own-employee-relations';

    public function __construct(private readonly StructureService $structures) {}

    public function canUseModule(?User $user): bool
    {
        return $user instanceof User && $user->canAny([self::SHOW, self::MANAGE]);
    }

    public function canCreate(?User $user): bool
    {
        return $user instanceof User && $user->canAny([self::CREATE, self::MANAGE]);
    }

    /**
     * @return Builder<RelationCase>
     */
    public function visibleQuery(?User $user): Builder
    {
        $query = RelationCase::query();

        if (! $user instanceof User || ! $this->canUseModule($user)) {
            return $query->whereRaw('1 = 0');
        }

        $this->structures->scopeFor($user)->constrain($query, 'employee_relation_cases.structure_id');

        if (! $user->can(self::MANAGE)) {
            $userId = (int) $user->getKey();

            $query->where(function (Builder $own) use ($userId): void {
                $own->where('employee_relation_cases.created_by', $userId)
                    ->orWhereExists(fn ($assigned) => $assigned
                        ->select(DB::raw(1))
                        ->from('employee_relation_case_investigators')
                        ->whereColumn('employee_relation_case_investigators.case_id', 'employee_relation_cases.id')
                        ->where('employee_relation_case_investigators.user_id', $userId));
            });
        }

        return $query;
    }

    public function canView(?User $user, RelationCase $case): bool
    {
        return $this->visibleQuery($user)->whereKey($case->getKey())->exists();
    }

    public function isInvestigator(?User $user, RelationCase $case): bool
    {
        return $user instanceof User && DB::table('employee_relation_case_investigators')
            ->where('case_id', $case->getKey())
            ->where('user_id', $user->getKey())
            ->exists();
    }

    /** İşi idarə edir: araşdırıcı təyini, mərhələ, qərar, apellyasiyaya baxış. */
    public function canManage(?User $user, RelationCase $case): bool
    {
        return $user instanceof User && $user->can(self::MANAGE) && $this->canView($user, $case);
    }

    /** İş üzərində işləyir: sübut, şahid, araşdırmaya başlama (idarəçi və ya araşdırıcı). */
    public function canWork(?User $user, RelationCase $case): bool
    {
        return $this->canManage($user, $case)
            || ($this->canView($user, $case) && $this->isInvestigator($user, $case));
    }

    /** İstifadəçi araşdırıcı ola bilərmi: modul icazəsi və işin strukturu görünürlüyündə. */
    public function canBeInvestigator(User $user, RelationCase $case): bool
    {
        if (! $this->canUseModule($user)) {
            return false;
        }

        return $this->structures->scopeFor($user)->allows($case->structure_id);
    }

    public function allowsPersonnel(User $user, ?int $personnelId): bool
    {
        return $personnelId === null || $this->structures->allowsPersonnelId($user, $personnelId);
    }
}
