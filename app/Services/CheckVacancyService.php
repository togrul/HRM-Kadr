<?php

namespace App\Services;

use App\Models\StaffSchedule;

class CheckVacancyService
{
    public function handle(array $components) : array
    {
        $result = [];
        $counts = collect();
        $staffs = StaffSchedule::select('structure_id','position_id','vacant')
            ->where('structure_id','>', 2)
            ->get()
            ->map(function ($item) {
                return [$item['structure_id'] . '-' . $item['position_id'] => $item['vacant']];
            })
            ->collapse();


        foreach ($components as $component)
        {
            $_generated_key = "{$component['structure_id']['id']}-{$component['position_id']['id']}";

            $counts[$_generated_key] = $counts->has($_generated_key) ?  $counts[$_generated_key] + 1 : 1;

            $messages[$_generated_key] =
                "{$component['structure_id']['name']} {$component['position_id']['name']} vakansiyası üzrə yer yoxdur.";
        }

        foreach ($counts as $key => $count)
        {
            $staffs[$key] = $staffs->has($key) ? $staffs[$key] : 0;

            if($staffs[$key] < $counts[$key])
            {
                $result = [
                    'count' => $counts[$key] - $staffs[$key],
                    'message' => $messages[$key] . " Ehtiyac olan yer sayı :{$count}",
                    'structure_id' => (int)explode('-',$key)[0],
                    'position_id' => (int)explode('-',$key)[1],
                ];
            }
        }

        return $result;
    }
}
