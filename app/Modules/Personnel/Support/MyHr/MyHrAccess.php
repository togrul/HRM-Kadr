<?php

namespace App\Modules\Personnel\Support\MyHr;

use App\Models\Personnel;
use App\Models\User;
use App\Services\UserPersonnelLinkResolver;
use Spatie\Permission\PermissionRegistrar;

class MyHrAccess
{
    private const SELF_SERVICE_ROLE = 'Employee Self-Service';

    private const BASELINE_PERMISSIONS = [
        'show-my-hr',
        'submit-self-service-leaves',
        'submit-self-service-vacations',
        'submit-self-service-business-trips',
        'request-own-request-correction',
        'view-own-onboarding-documents',
        'acknowledge-own-onboarding-documents',
        'view-own-learning-content',
        'view-own-personnel-documents',
        'view-own-hierarchy',
    ];

    public function __construct(
        private readonly UserPersonnelLinkResolver $resolver,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function authorize(?User $user): void
    {
        $user = $this->syncBaselinePermissions($user);
        abort_unless($this->canView($user), 403);
    }

    public function canView(?User $user): bool
    {
        $user = $this->syncBaselinePermissions($user);
        return (bool) $user?->can('show-my-hr');
    }

    public function canAccess(?User $user, string $permission): bool
    {
        $user = $this->syncBaselinePermissions($user);

        return (bool) $user?->can($permission);
    }

    public function resolvePersonnelId(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        $personnelId = $this->resolver->resolve($user);

        return $personnelId ? (int) $personnelId : null;
    }

    public function resolvePersonnel(?User $user): ?Personnel
    {
        $personnelId = $this->resolvePersonnelId($user);

        if (! $personnelId) {
            return null;
        }

        return Personnel::query()
            ->select([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'email',
                'phone',
                'mobile',
                'structure_id',
                'position_id',
                'join_work_date',
            ])
            ->with([
                'position:id,name',
                'structure' => fn ($query) => $query
                    ->select('id', 'parent_id', 'name')
                    ->withRecursive('parent', false),
            ])
            ->find($personnelId);
    }

    protected function syncBaselinePermissions(?User $user): ?User
    {
        if (! $user) {
            return null;
        }

        $isSelfService = $user->hasRole(self::SELF_SERVICE_ROLE) || $user->can('show-my-hr');
        if (! $isSelfService) {
            return $user;
        }

        $missing = array_values(array_filter(
            self::BASELINE_PERMISSIONS,
            fn (string $permission): bool => ! $user->can($permission)
        ));

        if ($missing === []) {
            return $user;
        }

        $user->givePermissionTo($missing);
        $this->permissionRegistrar->forgetCachedPermissions();

        return $user->fresh();
    }
}
