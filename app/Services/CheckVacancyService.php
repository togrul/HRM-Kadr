<?php

namespace App\Services;

use App\Models\StaffSchedule;

class CheckVacancyService
{
    public function handle(array $components): array
    {
        $result = [];
        $counts = [];
        $messages = [];

        foreach ($components as $component) {
            $structureId = (int) ($component['structure_id'] ?? 0);
            $positionId = (int) ($component['position_id'] ?? 0);

            if (! $structureId || ! $positionId) {
                continue;
            }

            $key = $structureId.'-'.$positionId;

            $counts[$key] = ($counts[$key] ?? 0) + 1;

            $structureName = $component['structure_label'] ?? (string) $structureId;
            $positionName = $component['position_label'] ?? (string) $positionId;

            $messages[$key] = "{$structureName} {$positionName} vakansiyası üzrə yer yoxdur.";
        }

        $staffs = StaffSchedule::select('structure_id', 'position_id', 'vacant')
            ->where('structure_id', '>', 2)
            ->get()
            ->map(function ($item) {
                return [$item['structure_id'].'-'.$item['position_id'] => $item['vacant']];
            })
            ->collapse();

        foreach ($counts as $key => $count) {
            $available = $staffs[$key] ?? 0;

            if ($available < $count) {
                $result = [
                    'count' => $count - $available,
                    'message' => ($messages[$key] ?? '') ." Ehtiyac olan yer sayı :$count",
                    'structure_id' => (int) explode('-', $key)[0],
                    'position_id' => (int) explode('-', $key)[1],
                ];
            }
        }

        return $result;
    }
}
