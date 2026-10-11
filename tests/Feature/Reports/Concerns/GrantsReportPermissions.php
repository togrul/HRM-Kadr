<?php

namespace Tests\Feature\Reports\Concerns;

use App\Models\User;
use App\Modules\Reports\Support\ReportPermissions;
use Spatie\Permission\Models\Permission;

/**
 * Hesabat testləri üçün icazə köməkçisi: modul girişi, istənilən hesabat qrupları və
 * əlavə icazələr (ixrac, preset paylaşımı, planlaşdırma, mənbə modul icazələri).
 */
trait GrantsReportPermissions
{
    /**
     * @param  list<string>|null  $groups  null — maaşdan başqa bütün qruplar
     * @param  list<string>  $extra  əlavə icazə adları
     */
    protected function grantReports(User $user, ?array $groups = null, bool $export = true, array $extra = []): User
    {
        $groups ??= array_values(array_diff(array_keys(ReportPermissions::GROUPS), [ReportPermissions::GROUP_PAYROLL]));

        $names = [ReportPermissions::VIEW];
        if ($export) {
            $names[] = ReportPermissions::EXPORT;
        }
        foreach ($groups as $group) {
            $names[] = ReportPermissions::GROUPS[$group];
        }
        $names = [...$names, ...$extra];

        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user->givePermissionTo($names);

        return $user;
    }
}
