<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

/**
 * İş növləri soraqçasının redaktəsi. İşi olan növ silinmir, yalnız passivləşdirilir —
 * köhnə işlərin növü və statistikası pozulmasın.
 */
class CaseTypeService
{
    /**
     * @param  array{name:string, description?:?string, investigation_sla_days?:?int, is_disciplinary?:bool, is_active?:bool, sort_order?:int}  $data
     *
     * @throws AuthorizationException
     */
    public function save(User $actor, array $data, ?int $typeId = null): RelationCaseType
    {
        if (! $actor->can(CaseAccess::MANAGE)) {
            throw new AuthorizationException;
        }

        $type = $typeId !== null ? RelationCaseType::query()->findOrFail($typeId) : new RelationCaseType;

        if (! $type->exists) {
            $type->code = $this->uniqueCode($data['name']);
        }

        $sla = (int) ($data['investigation_sla_days'] ?? 0);

        $type->fill([
            'name' => trim($data['name']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'investigation_sla_days' => $sla > 0 ? $sla : null,
            'is_disciplinary' => (bool) ($data['is_disciplinary'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? ($type->sort_order ?: ((int) RelationCaseType::query()->max('sort_order') + 10))),
        ])->save();

        activity(CaseAuditLogger::LOG_NAME)
            ->event($typeId === null ? 'type_created' : 'type_updated')
            ->performedOn($type)
            ->causedBy($actor)
            ->log('employee_relations.type_saved');

        return $type;
    }

    /**
     * @throws AuthorizationException
     */
    public function toggle(User $actor, int $typeId): RelationCaseType
    {
        if (! $actor->can(CaseAccess::MANAGE)) {
            throw new AuthorizationException;
        }

        $type = RelationCaseType::query()->findOrFail($typeId);
        $type->forceFill(['is_active' => ! $type->is_active])->save();

        return $type;
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_') ?: 'type', 40, '');
        $code = $base;
        $suffix = 2;

        while (RelationCaseType::query()->where('code', $code)->exists()) {
            $code = $base.'_'.$suffix++;
        }

        return $code;
    }
}
