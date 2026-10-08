<?php

namespace App\Modules\Candidates\Application\Services;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Personnel;
use App\Modules\Candidates\Contracts\CandidateHireReversal;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * İşə qəbul əmrinin təsdiqi geri alınanda namizədin vəziyyətini qəbuldan əvvəlki hala qaytarır.
 *
 * Asılı qeydlərin olmadığını çağıran tərəf (Orders) əvvəlcədən yoxlayır; bu servis yalnız
 * işə qəbulun özünün yaratdıqlarını geri alır: əməkdaş kartı (yumşaq silinmə), namizəd və
 * müraciət üzərindəki əlaqə sütunları, işə qəbulla açılmış adaptasiya hadisəsi.
 */
class CandidateHireReversalService implements CandidateHireReversal
{
    public function hiredPersonnelId(int $candidateId, int $orderId): ?int
    {
        if (! InstalledTables::hasColumn('candidates', 'hired_personnel_id')) {
            return null;
        }

        $candidate = Candidate::query()->whereKey($candidateId)->first(['id', 'hired_personnel_id', 'hire_order_id']);

        if (! $candidate || ! $candidate->hired_personnel_id) {
            return null;
        }

        // Yalnız məhz bu əmrlə yaradılmış əlaqə geri alınır.
        if ((int) $candidate->hire_order_id !== $orderId) {
            return null;
        }

        return (int) $candidate->hired_personnel_id;
    }

    public function revertOrderHire(int $candidateId, int $personnelId, int $orderId, string $reason): void
    {
        DB::transaction(function () use ($candidateId, $personnelId, $orderId, $reason): void {
            $candidate = Candidate::query()->lockForUpdate()->findOrFail($candidateId);
            $personnel = Personnel::query()->find($personnelId);

            if (! $personnel instanceof Personnel) {
                throw new RuntimeException("Hired personnel #{$personnelId} is missing.");
            }

            if (InstalledTables::hasColumn('candidate_applications', 'personnel_id')) {
                CandidateApplication::query()
                    ->where('candidate_id', $candidate->id)
                    ->where('personnel_id', $personnel->id)
                    ->update(['personnel_id' => null, 'converted_at' => null, 'converted_by' => null]);
            }

            if (InstalledTables::has('employee_lifecycle_events')) {
                DB::table('employee_lifecycle_events')
                    ->where('source_type', 'candidate_order_conversion')
                    ->where('source_id', $candidate->id)
                    ->where('personnel_id', $personnel->id)
                    ->delete();
            }

            $candidate->forceFill(['status_id' => CandidateHireOrderService::STATUS_READY_FOR_ORDER]);
            if (InstalledTables::hasColumn('candidates', 'hired_personnel_id')) {
                $candidate->forceFill([
                    'hired_personnel_id' => null,
                    'hire_order_id' => null,
                    'hire_order_no' => null,
                    'hired_at' => null,
                ]);
            }
            $candidate->save();

            activity('candidates')
                ->performedOn($personnel)
                ->withProperties([
                    'candidate_id' => $candidate->id,
                    'order_id' => $orderId,
                    'tabel_no' => $personnel->tabel_no,
                    'reason' => $reason,
                ])
                ->event('hire_revoked')
                ->log('candidate.hire_revoked');

            $personnel->delete();
        });
    }
}
