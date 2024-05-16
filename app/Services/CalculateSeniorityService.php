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

    public function calculateEducation($education)
    {
       $result = $this->calculate(
           $education['admission_year'],
           $education['graduated_year'] ?: Carbon::now()->format('Y-m-d') ,
           $education['coefficient']
       );
       $calculateCoefficientYearMonth = $this->calculateYearAndMonth($result['duration']);
       $result['year_coefficient'] = $calculateCoefficientYearMonth['year'];
       $result['month_coefficient'] = $calculateCoefficientYearMonth['month'];
       return $result;
    }

    public function calculateMultiEducation($edu_list)
    {
        $result = array();

        $result['data'] = collect($edu_list)->map(function($item){
            $item['duration'] = $this->calculate(
                $item['admission_year'],
                $item['graduated_year'] ?? Carbon::now()->format('Y-m-d'),
                $item['coefficient']
            );

            if(!empty($item['graduated_year']))
            {
                $item['old'] = $this->calculate(
                    $item['admission_year'],
                    $item['graduated_year'],
                    $item['coefficient']
                );
            }
            else
            {
                $item['current'] = $this->calculate(
                    $item['graduated_year'],
                    Carbon::now()->format('Y-m-d'),
                    $item['coefficient']
                );
            }

            if($item['calculate_as_seniority'])
            {
                $item['extra_seniority'] = $item['old'] + (array_key_exists('current',$item) ? $item['current'] : []);
                $item['coefficient'] = $this->calculateYearAndMonth($item['duration']['duration']);
            }
            else
            {
                $item['extra_seniority'] = 0;
                $item['coefficient'] = [];
            }

            return [
                'old' => $item['old'] ?? [],
                'current' => $item['current'] ?? [],
                'duration' => $item['duration'],
                'extra_seniority' => $item['extra_seniority'],
                'coefficient' => $item['coefficient']
            ];
        })->toArray();


        $result['extra_seniority'] = array_sum(array_column(array_column($result['data'],'extra_seniority'),'duration'));
        $result['extra_seniority_full'] = $this->calculateYearAndMonth($result['extra_seniority']);
        $result['total_duration'] = array_sum(array_column(array_column($result['data'],'duration'),'diff'));
        $result['total_duration_diff'] = $this->calculateYearAndMonth($result['total_duration']);

        return $result;
    }

    public function calculateMulti($list,$currentWorkType = 'military')
{
    $result = array();

    $result['data'] = collect($list)->map(function($item) use($currentWorkType){
        $leaveDate = $item['leave_date'] ?? Carbon::now();
        $item['duration'] = $this->calculate($item['join_date'], $leaveDate, $item['coefficient']);

        if (!$item['is_current']) {
            $key = $item['is_special_service'] ? 'old_military' : 'old';
            $item[$key] = $this->calculate($item['join_date'], $leaveDate, $item['coefficient']);
        } else {
            $item['current'] = $this->calculate($item['join_date'], $leaveDate, $item['coefficient']);
        }

        return [
            'old' => $item['old'] ?? [],
            'current' => $item['current'] ?? [],
            'duration' => $item['duration'],
        ];
    })->toArray();

    $old_month_list = $this->getColumnListFromArray($result['data'],'old','duration');
    $old_military_month_list = $this->getColumnListFromArray($result['data'],'old_military','duration');
    $full_current = $this->getColumnListFromArray($result['data'],'current','duration');
    $full_current_diff = $this->getColumnListFromArray($result['data'],'current','diff');
    $full_sum = $this->getColumnListFromArray($result['data'],'duration','duration');

    $result['sum_month_old'] = array_sum($old_month_list);
    $result['sum_month_military_old'] = array_sum($old_military_month_list);

    $result['sum_month_current'] = array_sum($full_current);
    $result['sum_month_current_diff'] = array_sum($full_current_diff);
    $result['sum_month'] = array_sum($full_sum);
    $result['sum_total'] = $result['sum_month_old'] + ($currentWorkType == 'military' ? 0 : $result['sum_month_current']);
    $result['sum_total_military'] = $result['sum_month_military_old'] + ($currentWorkType == 'military' ? $result['sum_month_current'] : 0);

    $result['sum_old'] = $this->calculateYearAndMonth($result['sum_month_old']);
    $result['sum_old_military'] = $this->calculateYearAndMonth($result['sum_month_military_old']);
    $result['sum_current'] = $this->calculateYearAndMonth($result['sum_month_current']);
    $result['sum_current_diff'] = $this->calculateYearAndMonth($result['sum_month_current_diff']);
    $result['sum_full'] = $this->calculateYearAndMonth($result['sum_month']);
    $result['sum_total_full'] = $this->calculateYearAndMonth($result['sum_total']);
    $result['sum_total_military_full'] = $this->calculateYearAndMonth($result['sum_total_military']);

    return $result;
}

    private function getColumnListFromArray(array $data,$parentColumnKey,$subColumnKey)
    {
        return array_column(array_column($data,$parentColumnKey),$subColumnKey);
    }

    public function calculateYearAndMonth($total_month)
    {
         $calculate['year'] = floor($total_month / 12);
         $calculate['month'] = $total_month % 12;

         return $calculate;
    }
}
