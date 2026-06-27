<?php

namespace App\Modules\Notifications\Support;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationAudienceResolver
{
    protected ?bool $performanceFormsTableReady = null;

    public function __construct(
        protected ApprovalRouteResolver $approvalRouteResolver,
    ) {}

    public function resolve(array $audienceConfig, ?Personnel $subject = null, array $context = []): Collection
    {
        $targets = $this->normalizedTargets($audienceConfig)
            ->map(fn ($target) => is_string($target) ? trim($target) : null)
            ->filter()
            ->unique()
            ->values();

        if ($targets->isEmpty()) {
            return collect();
        }

        $users = collect();

        foreach ($targets as $target) {
            $users = $users->merge(match ($target) {
                'employee' => $this->employeeUser($subject),
                'all_employees' => $this->allEmployees(),
                'admins' => $this->admins(),
                'hr' => $this->hrUsers(),
                'same_structure' => $this->sameStructureUsers($subject, $context),
                'direct_manager' => $this->directManagerUsers($subject, $context),
                'manager_chain' => $this->managerChainUsers($subject),
                'department' => $this->departmentUsers($audienceConfig, $subject, $context),
                'specific_users' => $this->specificUsers($audienceConfig),
                'notification_permission' => $this->notificationPermissionUsers(),
                default => collect(),
            });
        }

        return $users
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();
    }

    protected function normalizedTargets(array $audienceConfig): Collection
    {
        $targets = collect((array) data_get($audienceConfig, 'targets', []));

        $structureIds = collect((array) data_get($audienceConfig, 'structure_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0);

        $userIds = collect((array) data_get($audienceConfig, 'user_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0);

        if ($structureIds->isNotEmpty() && ! $targets->contains('department')) {
            $targets->push('department');
        }

        if ($userIds->isNotEmpty() && ! $targets->contains('specific_users')) {
            $targets->push('specific_users');
        }

        return $targets;
    }

    protected function employeeUser(?Personnel $subject): Collection
    {
        if (! $subject || blank($subject->email)) {
            return collect();
        }

        return User::query()
            ->where('email', $subject->email)
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function allEmployees(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('personnel', function ($query) {
                $query->whereNull('leave_work_date')
                    ->where('is_pending', false);
            })
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function admins(): Collection
    {
        return User::role('admin')
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function hrUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr');
            })
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function sameStructureUsers(?Personnel $subject, array $context = []): Collection
    {
        $structureId = $this->resolveStructureId($subject, $context);

        if (! $structureId) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('personnel', function ($query) use ($structureId) {
                $query->where('structure_id', $structureId)
                    ->whereNull('leave_work_date')
                    ->where('is_pending', false);
            })
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function directManagerUsers(?Personnel $subject, array $context = []): Collection
    {
        if ($subject && $this->performanceFormsTableReady()) {
            $subject->loadMissing('latestManagerAssignment.manager');

            $manager = $subject->latestManagerAssignment?->manager;

            if ($manager instanceof User && (bool) $manager->is_active) {
                return collect([$manager]);
            }
        }

        $managerId = (int) data_get($context, 'direct_manager_user_id', 0);

        if ($managerId > 0) {
            return User::query()
                ->whereKey($managerId)
                ->where('is_active', true)
                ->get(['id', 'name', 'email', 'is_active']);
        }

        return collect();
    }

    protected function managerChainUsers(?Personnel $subject): Collection
    {
        if (! $subject) {
            return collect();
        }

        $managerIds = collect($this->approvalRouteResolver->managerChain($subject))
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($managerIds->isEmpty()) {
            return collect();
        }

        $links = UserPersonnelLink::query()
            ->whereIn('personnel_id', $managerIds->all())
            ->pluck('user_id', 'personnel_id');

        $linkedUsers = User::query()
            ->where('is_active', true)
            ->whereIn('id', $links->values()->filter()->unique()->all())
            ->get(['id', 'name', 'email', 'is_active'])
            ->keyBy('id');

        $resolved = $managerIds
            ->map(function (int $personnelId) use ($links, $linkedUsers) {
                $userId = (int) ($links->get($personnelId) ?? 0);

                return $userId > 0 ? $linkedUsers->get($userId) : null;
            })
            ->filter();

        $unresolvedIds = $managerIds
            ->reject(fn (int $personnelId) => $links->has($personnelId))
            ->values();

        if ($unresolvedIds->isEmpty()) {
            return $resolved->unique('id')->values();
        }

        $fallbackPersonnels = Personnel::query()
            ->active()
            ->whereIn('id', $unresolvedIds->all())
            ->get(['id', 'email']);

        $emailMap = $fallbackPersonnels
            ->mapWithKeys(function (Personnel $personnel): array {
                $email = Str::lower(trim((string) $personnel->email));

                return $email !== '' ? [$personnel->id => $email] : [];
            });

        if ($emailMap->isEmpty()) {
            return $resolved->unique('id')->values();
        }

        $fallbackUsers = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($emailMap): void {
                foreach ($emailMap->unique()->values() as $email) {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                }
            })
            ->get(['id', 'name', 'email', 'is_active'])
            ->keyBy(fn (User $user) => Str::lower(trim((string) $user->email)));

        return $resolved
            ->concat(
                $unresolvedIds->map(function (int $personnelId) use ($emailMap, $fallbackUsers) {
                    $email = $emailMap->get($personnelId);

                    return $email ? $fallbackUsers->get($email) : null;
                })->filter()
            )
            ->unique('id')
            ->values();
    }

    protected function departmentUsers(array $audienceConfig, ?Personnel $subject, array $context = []): Collection
    {
        $structureIds = collect((array) data_get($audienceConfig, 'structure_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($structureIds->isEmpty()) {
            $structureId = $this->resolveStructureId($subject, $context);

            if ($structureId) {
                $structureIds = collect([(int) $structureId]);
            }
        }

        if ($structureIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('personnel', function ($query) use ($structureIds) {
                $query->whereIn('structure_id', $structureIds->all())
                    ->whereNull('leave_work_date')
                    ->where('is_pending', false);
            })
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function specificUsers(array $audienceConfig): Collection
    {
        $userIds = collect((array) data_get($audienceConfig, 'user_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $userIds->all())
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function notificationPermissionUsers(): Collection
    {
        return User::permission('get-notification')
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'is_active']);
    }

    protected function resolveStructureId(?Personnel $subject, array $context = []): ?int
    {
        if ($subject?->structure_id) {
            return (int) $subject->structure_id;
        }

        $contextStructureId = data_get($context, 'structure_id');

        return $contextStructureId ? (int) $contextStructureId : null;
    }

    protected function performanceFormsTableReady(): bool
    {
        if ($this->performanceFormsTableReady !== null) {
            return $this->performanceFormsTableReady;
        }

        return $this->performanceFormsTableReady = Schema::hasTable('performance_forms');
    }
}
