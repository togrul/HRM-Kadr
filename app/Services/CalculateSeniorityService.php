<?php

namespace App\Services;

use Carbon\Carbon;

class CalculateSeniorityService
{
    public function calculate(
        $join_date,
        $leave_date,
        $coefficient
    )
    {
        $data['diff'] = Carbon::parse($join_date)
            ->diffInMonths(Carbon::parse($leave_date));

        $data['duration'] = !empty($coefficient)
            ? $coefficient * $data['diff']
            : $data['diff'];

        $y_m = $this->calculateYearAndMonth($data['diff']);
        $data['year'] = $y_m['year'];
        $data['month'] = $y_m['month'];

        return $data;
     }

     public function calculateYearAndMonth($total_month)
     {
         $calculate['year'] = floor($total_month / 12);
         $calculate['month'] = $total_month % 12;

         return $calculate;
     }
}
