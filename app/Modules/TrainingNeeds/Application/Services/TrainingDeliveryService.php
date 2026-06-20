<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingNeedItem;
use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use Illuminate\Support\Facades\DB;

class TrainingDeliveryService
{
    public function completeSession(TrainingSession $session): array
    {
        return DB::transaction(function () use ($session): array {
            $session->loadMissing([
                'program:id,duration_hours',
                'participants.trainingNeed:id,training_competency_id,recommended_program_id,status',
            ]);

            $completedAt = now();
            $attendedCount = 0;
            $recordCount = 0;

            foreach ($session->participants as $participant) {
                if (! in_array($participant->attendance_status, ['attended', 'completed'], true)) {
                    continue;
                }

                $participant->forceFill([
                    'attendance_status' => 'attended',
                    'attended_at' => $participant->attended_at ?? $completedAt,
                ])->save();

                $need = $participant->trainingNeed;

                TrainingDeliveryRecord::query()->updateOrCreate(
                    [
                        'training_session_id' => $session->id,
                        'personnel_id' => $participant->personnel_id,
                    ],
                    [
                        'training_program_id' => $session->training_program_id,
                        'training_competency_id' => $need?->training_competency_id,
                        'training_need_item_id' => $participant->training_need_item_id,
                        'attended_hours' => $session->program?->duration_hours,
                        'result_status' => 'completed',
                        'completed_at' => $completedAt,
                    ]
                );

                if ($need instanceof TrainingNeedItem) {
                    $need->forceFill([
                        'status' => 'completed',
                        'recommended_program_id' => $need->recommended_program_id ?: $session->training_program_id,
                    ])->save();
                }

                $attendedCount++;
                $recordCount++;
            }

            $session->forceFill([
                'status' => 'completed',
                'completed_at' => $completedAt,
            ])->save();

            return [
                'attended_count' => $attendedCount,
                'record_count' => $recordCount,
            ];
        });
    }
}
