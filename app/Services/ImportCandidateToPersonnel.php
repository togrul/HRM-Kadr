<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class ImportCandidateToPersonnel
{
    public function __construct(
        protected PersonnelTabelNoGeneratorService $tabelNoGenerator
    ) {
    }

    public function handle(array $components, $status): array
    {
        $tabel_no_list = [];
        // dd($components);
        foreach ($components as $component) {
            $candidateId = $component['personnel_id'] ?? null;
            if (is_array($candidateId)) {
                $candidateId = $candidateId['id'] ?? null;
            }
            if (! $candidateId) {
                continue;
            }

            $candidate = Candidate::find($candidateId);
            if (! $candidate) {
                continue;
            }
            $structureId = $this->valueAsInt($component, 'structure_id');
            $positionId = $this->valueAsInt($component, 'position_id');

            $isPending = $status != OrderStatusEnum::APPROVED->value;
            $joinDate = Carbon::now()->format('Y-m-d');
            $tabelNo = $isPending
                ? "NMZD{$candidate->id}"
                : $this->tabelNoGenerator->generateForJoinDate($joinDate);

            if (Personnel::withTrashed()->where('tabel_no', $tabelNo)->exists()) {
                throw new RuntimeException(__('orders::order_form.messages.candidate_already_imported', [
                    'candidate' => $candidate->fullname,
                    'tabel_no' => $tabelNo,
                ]));
            }

            try {
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
                    'is_pending' => $isPending,
                    'tabel_no' => $tabelNo,
                    'mobile' => '1234567',
                    'email' => 'email@example.com',
                    'nationality_id' => 11,
                    'education_degree_id' => 100,
                    'pin' => '1234567',
                    'residental_address' => 'yoxdur',
                    'registered_address' => 'yoxdur',
                    'work_norm_id' => 10,
                    'join_work_date' => $joinDate,
                ]);
            } catch (QueryException $exception) {
                if ($this->isDuplicatePersonnelTabelQuery($exception)) {
                    throw new RuntimeException(__('orders::order_form.messages.personnel_duplicate_tabel_no', [
                        'tabel_no' => $tabelNo,
                    ]), previous: $exception);
                }

                throw $exception;
            }
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

    protected function isDuplicatePersonnelTabelQuery(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'duplicate entry')
            && str_contains($message, 'personnels_tabel_no_unique');
    }
}
