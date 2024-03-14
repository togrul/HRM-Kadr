<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\Personnel;
use Carbon\Carbon;

class ImportCandidateToPersonnel
{
    public function handle(array $candidates,$status)
    {
        $candidateList = Candidate::find($candidates);
        $tabel_no_list = [];

        foreach ($candidateList as $candidate)
        {
           $personnel = Personnel::create([
                'surname' => $candidate->surname,
                'name' => $candidate->name,
                'patronymic' => $candidate->patronymic,
                'phone' => $candidate->phone,
                'structure_id' => $candidate->structure_id,
                'position_id' => 21,
                'gender' => $candidate->gender,
                'birthdate' => $candidate->birthdate,
                'is_pending' => $status != OrderStatusEnum::APPROVED->value,
                'tabel_no' => "NMZD{$candidate->id}",
                'mobile' => '1234567',
                'email' => 'email@example.com',
                'nationality_id' => 11,
                'education_degree_id' => 100,
                'pin' => '1234567',
                'residental_address' => ' ',
                'registered_address' => ' ',
                'work_norm_id' => 10,
                'join_work_date' => Carbon::now()->format('Y-m-d')
            ]);
            $tabel_no_list[] = $personnel->tabel_no;
        }
        return $tabel_no_list;
    }
}
