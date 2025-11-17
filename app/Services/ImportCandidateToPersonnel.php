<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\Personnel;
use Carbon\Carbon;

class ImportCandidateToPersonnel
{
    public function handle(array $components, $status): array
    {
        $tabel_no_list = [];
        foreach ($components as $component) {
            $candidate = Candidate::find($component['personnel_id']);
            $structureId = $this->valueAsInt($component, 'structure_id');
            $positionId = $this->valueAsInt($component, 'position_id');

            $personnel = Personnel::create([
                'surname' => $candidate->surname,
                'name' => $candidate->name,
                'patronymic' => $candidate->patronymic,
                'phone' => $candidate->phone,
                'structure_id' => $structureId,
                'position_id' => $positionId,
                'gender' => $candidate->gender,
                'referenced_by' => $candidate->presented_by,
                'birthdate' => Carbon::parse($candidate->birthdate)->format('Y-m-d'),
                'is_pending' => $status != OrderStatusEnum::APPROVED->value,
                'tabel_no' => "NMZD{$candidate->id}",
                'mobile' => '1234567',
                'email' => 'email@example.com',
                'nationality_id' => 11,
                'education_degree_id' => 100,
                'pin' => '1234567',
                'residental_address' => 'yoxdur',
                'registered_address' => 'yoxdur',
                'work_norm_id' => 10,
                'join_work_date' => Carbon::now()->format('Y-m-d'),
            ]);
            $tabel_no_list[] = $personnel->tabel_no;
        }

        return $tabel_no_list;
    }

    protected function valueAsInt(array $component, string $field): ?int
    {
        $value = $component[$field] ?? null;
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        return $value !== null ? (int) $value : null;
    }
}
