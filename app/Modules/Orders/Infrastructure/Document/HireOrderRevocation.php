<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Contracts\AbsenceSource;
use App\Contracts\EmployeeRecordSource;
use App\Data\AbsencePeriod;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Candidates\Contracts\CandidateHireReversal;
use App\Modules\Compensation\Contracts\OrderCompensationSync;
use App\Services\Staff\StaffScheduleVacancyService;
use Carbon\CarbonImmutable;
use DomainException;

/**
 * Təsdiqlənmiş işə qəbul əmrinin təsdiqinin geri alınması.
 *
 * Qayda: işə qəbul yalnız yaradılmış əməkdaşın hələ heç bir asılı qeydi yoxdursa geri alınır —
 * başqa əmr, məzuniyyət/icazə/ezamiyyət (gözləyən və ya təsdiqlənmiş), davamiyyət faktı və ya
 * tabel günü, əmək haqqı hesablaması, kompensasiya/bank qeydi, kadr hərəkəti. Bunu hər modul
 * özü {@see EmployeeRecordSource} (və {@see AbsenceSource}) vasitəsilə bildirir, Orders başqa
 * modulun cədvəllərini oxumur. Qeyd varsa geri alma rədd edilir: artıq işləmiş əməkdaşla
 * münasibət əmək müqaviləsinə xitam əmri ilə bitirilməlidir.
 *
 * Geri alma: işə qəbulun yaratdığı avtomatik əmək haqqı layihəsi silinir, əməkdaş yumşaq silinir
 * (səbəb audit jurnalında), namizəd "Əmrə hazır" statusuna qayıdır, ştat yeri boşalır.
 */
class HireOrderRevocation
{
    /** Absence növü => qeyd açarı. */
    private const ABSENCE_KEYS = [
        AbsencePeriod::TYPE_VACATION => 'vacations',
        AbsencePeriod::TYPE_LEAVE => 'leaves',
        AbsencePeriod::TYPE_BUSINESS_TRIP => 'business_trips',
    ];

    public function __construct(
        private readonly CandidateHireReversal $candidates,
        private readonly OrderCompensationSync $compensation,
        private readonly StaffScheduleVacancyService $vacancies,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     *
     * @throws DomainException geri alma mümkün olmadıqda
     */
    public function revoke(OrderLog $order, array $snapshot): void
    {
        $candidateId = (int) ($snapshot['candidate_id'] ?? 0);

        // Namizəd və ya vəzifə olmadan təsdiq heç kimi işə qəbul etməmişdi — geri alınacaq bir şey yoxdur.
        if ($candidateId <= 0 || empty($snapshot['hire_position_id'])) {
            return;
        }

        $personnelId = $this->candidates->hiredPersonnelId($candidateId, (int) $order->id);
        $personnel = $personnelId ? Personnel::query()->find($personnelId) : null;

        if (! $personnel instanceof Personnel) {
            throw new DomainException(__('orders::order_composer.errors.hire_revoke_unlinked'));
        }

        $records = $this->dependentRecords($personnel);
        if ($records !== []) {
            throw new DomainException(__('orders::order_composer.errors.hire_revoke_blocked', [
                'records' => implode(', ', $records),
            ]));
        }

        $structureId = $personnel->structure_id ? (int) $personnel->structure_id : null;
        $positionId = $personnel->position_id ? (int) $personnel->position_id : null;

        $this->compensation->discardHireDraft((string) $personnel->tabel_no, $order->order_no ? (string) $order->order_no : null);

        $this->candidates->revertOrderHire(
            $candidateId,
            (int) $personnel->id,
            (int) $order->id,
            __('orders::order_composer.hire_revoke.reason', ['order' => (string) $order->order_no]),
        );

        $this->vacancies->releaseForRevokedHire($structureId, $positionId);
    }

    /**
     * Əməkdaşın asılı qeydləri, oxunaqlı formada ("məzuniyyətlər: 1"). Boş siyahı — qeyd yoxdur.
     *
     * @return list<string>
     */
    public function dependentRecords(Personnel $personnel): array
    {
        $tabelNo = (string) $personnel->tabel_no;
        $counts = [];

        /** @var EmployeeRecordSource $source */
        foreach (app()->tagged(EmployeeRecordSource::TAG) as $source) {
            foreach ($source->recordCounts((int) $personnel->id, $tabelNo) as $key => $count) {
                $counts[$key] = ($counts[$key] ?? 0) + $count;
            }
        }

        $from = CarbonImmutable::create(1900, 1, 1);
        $to = CarbonImmutable::create(2999, 12, 31);

        /** @var AbsenceSource $source */
        foreach (app()->tagged(AbsenceSource::TAG) as $source) {
            foreach ($source->overlapping($tabelNo, $from, $to) as $period) {
                $key = self::ABSENCE_KEYS[$period->type] ?? 'absences';
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        $labels = [];
        foreach ($counts as $key => $count) {
            if ($count > 0) {
                $labels[] = __('orders::order_composer.hire_revoke.records.'.$key).': '.$count;
            }
        }

        return $labels;
    }
}
