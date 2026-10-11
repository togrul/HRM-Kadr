<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\User;
use App\Modules\Reports\Support\ReportPermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Hesabat icazələrinin yeganə yoxlama nöqtəsi. İstifadəçi verilməyəndə cari istifadəçiyə
 * baxır; planlı hesabat isə sahibini açıq ötürür ki, fayl sahibin icazələri ilə qurulsun.
 */
class ReportsAccessService
{
    /**
     * @throws AuthorizationException
     */
    public function authorizeView(): void
    {
        abort_unless($this->canView(), 403);
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeExport(): void
    {
        abort_unless($this->canExport(), 403);
    }

    public function authorizeStandardReport(string $report): void
    {
        abort_unless($this->canViewStandardReport($report), 403);
    }

    public function authorizeDynamicSource(string $source): void
    {
        abort_unless($this->canViewDynamicSource($source), 403);
    }

    public function canView(?Authenticatable $user = null): bool
    {
        $user = $this->resolve($user);

        return $user !== null && $user->canAny([ReportPermissions::VIEW, ReportPermissions::EXPORT]);
    }

    public function canExport(?Authenticatable $user = null): bool
    {
        $user = $this->resolve($user);

        return $user !== null && $user->can(ReportPermissions::EXPORT);
    }

    public function canManageSharedPresets(?Authenticatable $user = null): bool
    {
        $user = $this->resolve($user);

        return $user !== null && $this->canView($user) && $user->can(ReportPermissions::MANAGE_PRESETS);
    }

    /**
     * Planlı hesabat faylı ixracdır: göndərmək üçün ixrac icazəsi də lazımdır.
     */
    public function canSchedule(?Authenticatable $user = null): bool
    {
        $user = $this->resolve($user);

        return $user !== null
            && $this->canView($user)
            && $user->can(ReportPermissions::SCHEDULE)
            && $user->can(ReportPermissions::EXPORT);
    }

    public function canViewGroup(string $group, ?Authenticatable $user = null): bool
    {
        $user = $this->resolve($user);
        $permission = ReportPermissions::GROUPS[$group] ?? null;

        if ($user === null || $permission === null || ! $this->canView($user) || ! $user->can($permission)) {
            return false;
        }

        foreach (ReportPermissions::GROUP_PREREQUISITES[$group] ?? [] as $prerequisite) {
            if (! $user->can($prerequisite)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function visibleGroups(?Authenticatable $user = null): array
    {
        $user = $this->resolve($user);

        return array_values(array_filter(
            array_keys(ReportPermissions::GROUPS),
            fn (string $group): bool => $this->canViewGroup($group, $user)
        ));
    }

    public function canViewStandardReport(string $report, ?Authenticatable $user = null): bool
    {
        $group = ReportPermissions::groupOfStandardReport($report);

        return $group !== null && $this->canViewGroup($group, $user);
    }

    public function canViewDynamicSource(string $source, ?Authenticatable $user = null): bool
    {
        $group = ReportPermissions::groupOfDynamicSource($source);

        return $group !== null && $this->canViewGroup($group, $user);
    }

    /**
     * @param  array<int,array<string,mixed>>  $options  `key` daşıyan standart hesabat seçimləri
     * @return array<int,array<string,mixed>>
     */
    public function filterStandardReports(array $options, ?Authenticatable $user = null): array
    {
        $user = $this->resolve($user);

        return array_values(array_filter(
            $options,
            fn (array $option): bool => $this->canViewStandardReport((string) ($option['key'] ?? ''), $user)
        ));
    }

    /**
     * @param  array<int,array<string,mixed>>  $options  `key` daşıyan dinamik mənbə seçimləri
     * @return array<int,array<string,mixed>>
     */
    public function filterDynamicSources(array $options, ?Authenticatable $user = null): array
    {
        $user = $this->resolve($user);

        return array_values(array_filter(
            $options,
            fn (array $option): bool => $this->canViewDynamicSource((string) ($option['key'] ?? ''), $user)
        ));
    }

    /**
     * Müqayisə kartlarından istifadəçinin qrupuna düşənlər.
     *
     * @return list<string>
     */
    public function visibleComparisonCards(?Authenticatable $user = null): array
    {
        $groups = $this->visibleGroups($user);

        return array_keys(array_filter(
            ReportPermissions::COMPARISON_GROUPS,
            fn (string $group): bool => in_array($group, $groups, true)
        ));
    }

    private function resolve(?Authenticatable $user): ?User
    {
        $user ??= auth()->user();

        return $user instanceof User ? $user : null;
    }
}
