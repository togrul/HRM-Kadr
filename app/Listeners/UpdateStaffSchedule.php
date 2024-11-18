<?php

namespace App\Listeners;

use App\Events\StaffScheduleUpdated;
use App\Models\StaffSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateStaffSchedule
{
    /**
     * Handle the event.
     */
    public function handle(StaffScheduleUpdated $event): void
    {
        $action = $event->action;
        $staff = StaffSchedule::where('structure_id', $event->structure_id)
            ->where('position_id', $event->position_id)
            ->first();

        if ($staff) {
            if ($action === 'increase') {
                $staff->update([
                    'filled' => $staff->filled + 1,
                    'vacant' => max($staff->vacant - 1, 0),
                ]);
            } elseif ($action === 'decrease') {
                $staff->update([
                    'vacant' => $staff->vacant + 1,
                    'filled' => max($staff->filled - 1, 0),
                ]);
            }
        }
    }
}
