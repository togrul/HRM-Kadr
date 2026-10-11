<?php

namespace App\Modules\EmployeeRelations\Policies;

use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;

/**
 * Qeyd səviyyəli yoxlama CaseAccess-ə ötürülür (struktur görünürlüyü + təyinat + sahib).
 */
class RelationCasePolicy
{
    public function __construct(private readonly CaseAccess $access) {}

    public function viewAny(User $user): bool
    {
        return $this->access->canUseModule($user);
    }

    public function view(User $user, RelationCase $case): bool
    {
        return $this->access->canView($user, $case);
    }

    public function create(User $user): bool
    {
        return $this->access->canCreate($user);
    }

    public function update(User $user, RelationCase $case): bool
    {
        return $this->access->canWork($user, $case);
    }

    public function manage(User $user, RelationCase $case): bool
    {
        return $this->access->canManage($user, $case);
    }
}
