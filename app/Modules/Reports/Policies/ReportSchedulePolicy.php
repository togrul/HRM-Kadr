<?php

namespace App\Modules\Reports\Policies;

use App\Models\User;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Domain\Models\ReportSchedule;

/**
 * Planlı hesabatı yalnız sahibi görür və idarə edir; hər əməliyyat üçün `schedule-reports`
 * (və ixrac) icazəsi hələ də qüvvədə olmalıdır.
 */
class ReportSchedulePolicy
{
    public function __construct(private ReportsAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $this->access->canSchedule($user);
    }

    public function create(User $user): bool
    {
        return $this->access->canSchedule($user);
    }

    public function view(User $user, ReportSchedule $schedule): bool
    {
        return $this->owns($user, $schedule);
    }

    public function update(User $user, ReportSchedule $schedule): bool
    {
        return $this->owns($user, $schedule);
    }

    public function delete(User $user, ReportSchedule $schedule): bool
    {
        return $this->owns($user, $schedule);
    }

    private function owns(User $user, ReportSchedule $schedule): bool
    {
        return $this->access->canSchedule($user) && (int) $schedule->user_id === (int) $user->getKey();
    }
}
