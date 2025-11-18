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

        if (empty($counts)) {
            return [];
        }

        [$structureIds, $positionIds] = $this->extractLookupIds(array_keys($counts));

        $staffs = StaffSchedule::query()
            ->select('structure_id', 'position_id', 'vacant')
            ->when(! empty($structureIds), fn ($q) => $q->whereIn('structure_id', $structureIds))
            ->when(! empty($positionIds), fn ($q) => $q->whereIn('position_id', $positionIds))
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->structure_id.'-'.$item->position_id => (int) $item->vacant,
            ]);

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

    private function extractLookupIds(array $keys): array
    {
        $structureIds = [];
        $positionIds = [];

        foreach ($keys as $key) {
            [$structureId, $positionId] = array_map('intval', explode('-', $key));
            $structureIds[] = $structureId;
            $positionIds[] = $positionId;
        }

        return [array_values(array_unique($structureIds)), array_values(array_unique($positionIds))];
    }
}
