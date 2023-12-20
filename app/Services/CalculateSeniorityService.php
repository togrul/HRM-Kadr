<?php

namespace App\Services;

use Carbon\Carbon;

class CalculateSeniorityService
{
    public function calculate(   
        $join_date, 
        $leave_date,
        $coefficient,
        $total_duration
    )
    {
        $data['diff'] = Carbon::parse($join_date)
                        ->diffInMonths(Carbon::parse($leave_date));                
        $data['duration'] = !empty($coefficient) 
                            ? $coefficient * $data['diff'] 
                            : $data['diff'];
        $data['total_duration'] = $total_duration;
        $data['total_duration'] += $data['duration']; 

        $data['year'] = floor($data['diff'] / 12);
        $data['month'] = $data['diff'] % 12;
        $data['total_year_old'] = floor($data['total_duration'] / 12);
        $data['total_month_old'] = $data['total_duration'] % 12;

        return $data;
     }
}