<?php

namespace App\Modules\Reports\Policies;

use App\Models\User;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Domain\Models\ReportPreset;

/**
 * Şəxsi şablonu yalnız sahibi görür və idarə edir. Paylaşılan şablonu hər hesabat istifadəçisi
 * görüb tətbiq edə bilər, dəyişmək/silmək isə sahibinə və `manage-report-presets` olanlara
 * açıqdır. Şablonu paylaşmaq üçün də həmin icazə lazımdır.
 */
class ReportPresetPolicy
{
    public function __construct(private ReportsAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $this->access->canView($user);
    }

    public function view(User $user, ReportPreset $preset): bool
    {
        return $this->access->canView($user) && ($preset->isOwnedBy($user) || $preset->is_shared);
    }

    public function create(User $user): bool
    {
        return $this->access->canView($user);
    }

    public function update(User $user, ReportPreset $preset): bool
    {
        if (! $this->access->canView($user)) {
            return false;
        }

        // Paylaşılan şablon hamıya təsir edir: sahibi də paylaşma icazəsini itiribsə dəyişə bilmir.
        if ($preset->is_shared) {
            return $this->access->canManageSharedPresets($user);
        }

        return $preset->isOwnedBy($user);
    }

    public function delete(User $user, ReportPreset $preset): bool
    {
        if (! $this->access->canView($user)) {
            return false;
        }

        return $preset->isOwnedBy($user) || ($preset->is_shared && $this->access->canManageSharedPresets($user));
    }

    public function share(User $user): bool
    {
        return $this->access->canManageSharedPresets($user);
    }
}
