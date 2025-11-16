<?php

namespace App\Services;

use App\Models\Position;
use App\Models\StaffSchedule;
use App\Models\Structure;

class CheckVacancyService
{
    public function handle(array $components): array
    {
        $result = [];
        $counts = collect();
        $messages = [];

        $structureIds = collect($components)
            ->map(fn ($component) => $this->extractId($component, 'structure_id'))
            ->filter()
            ->unique()
            ->values();

        $positionIds = collect($components)
            ->map(fn ($component) => $this->extractId($component, 'position_id'))
            ->filter()
            ->unique()
            ->values();

        $structureLabels = Structure::query()
            ->whereIn('id', $structureIds)
            ->pluck('name', 'id');

        $positionLabels = Position::query()
            ->whereIn('id', $positionIds)
            ->pluck('name', 'id');

        $staffs = StaffSchedule::select('structure_id', 'position_id', 'vacant')
            ->where('structure_id', '>', 2)
            ->get()
            ->map(function ($item) {
                return [$item['structure_id'].'-'.$item['position_id'] => $item['vacant']];
            })
            ->collapse();

        foreach ($components as $component) {
            $structureId = $this->extractId($component, 'structure_id');
            $positionId = $this->extractId($component, 'position_id');

            if (! $structureId || ! $positionId) {
                continue;
            }

            $_generated_key = "{$structureId}-{$positionId}";

            $counts[$_generated_key] = $counts->has($_generated_key) ? $counts[$_generated_key] + 1 : 1;

            $structureName = data_get($component, 'structure_id.name')
                ?? $structureLabels->get($structureId, (string) $structureId);
            $positionName = data_get($component, 'position_id.name')
                ?? $positionLabels->get($positionId, (string) $positionId);

            $messages[$_generated_key] = "{$structureName} {$positionName} vakansiyası üzrə yer yoxdur.";
        }

        foreach ($counts as $key => $count) {
            $staffs[$key] = $staffs->has($key) ? $staffs[$key] : 0;

            if ($staffs[$key] < $counts[$key]) {
                $result = [
                    'count' => $counts[$key] - $staffs[$key],
                    'message' => $messages[$key]." Ehtiyac olan yer sayı :$count",
                    'structure_id' => (int) explode('-', $key)[0],
                    'position_id' => (int) explode('-', $key)[1],
                ];
            }
        }

        return $result;
    }

    protected function extractId(array $component, string $field): ?int
    {
        $value = $component[$field] ?? null;
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        return $value !== null ? (int) $value : null;
    }
}
