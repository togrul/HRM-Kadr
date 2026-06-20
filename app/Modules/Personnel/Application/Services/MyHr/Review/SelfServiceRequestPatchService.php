<?php

namespace App\Modules\Personnel\Application\Services\MyHr\Review;

use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use Carbon\Carbon;

class SelfServiceRequestPatchService
{
    public function apply(object $requestable, array $patch): void
    {
        if ($patch === []) {
            return;
        }

        if ($requestable instanceof Leave) {
            $requestable->forceFill(array_intersect_key($patch, array_flip([
                'starts_at', 'ends_at', 'reason', 'duration_unit', 'partial_day_part', 'starts_time', 'ends_time',
            ])))->save();

            return;
        }

        if ($requestable instanceof PersonnelVacation) {
            $fillable = array_intersect_key($patch, array_flip(['vacation_places', 'start_date', 'end_date']));
            if (isset($fillable['start_date'], $fillable['end_date'])) {
                $end = Carbon::parse($fillable['end_date'])->startOfDay();
                $start = Carbon::parse($fillable['start_date'])->startOfDay();
                $fillable['duration'] = $start->diffInDays($end) + 1;
                $fillable['return_work_date'] = $end->copy()->addDay()->toDateString();
            }

            $requestable->forceFill($fillable)->save();

            return;
        }

        if ($requestable instanceof PersonnelBusinessTrip) {
            $requestable->forceFill(array_intersect_key($patch, array_flip([
                'location', 'description', 'start_date', 'end_date',
            ])))->save();
        }
    }
}
