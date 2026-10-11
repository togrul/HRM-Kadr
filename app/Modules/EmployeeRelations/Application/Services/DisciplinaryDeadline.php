<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Contracts\AbsenceSource;
use App\Data\AbsencePeriod;
use App\Models\Personnel;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * İntizam tənbehinin hüquqi müddətləri (Əmək Məcəlləsi):
 *
 *  - Md.187.2: tənbeh xətanın aşkar edildiyi gündən bir ay keçənədək verilə bilər; işçinin
 *    əmək qabiliyyətini müvəqqəti itirdiyi və məzuniyyətdə olduğu vaxt bu müddətə daxil
 *    edilmir. Burada həmin günlər işçinin məzuniyyət və icazə (xəstəlik vərəqəsi daxil)
 *    qeydlərindən — modulların AbsenceSource kontraktı ilə — götürülür.
 *  - Md.187.3: xətanın törədildiyi gündən altı ay keçdikdən sonra tənbeh verilə bilməz;
 *    audit/təftişlə aşkar edilmiş vəzifə hüquqpozmasında bu müddət iki ildir.
 *  - Md.187.1: tənbehdən əvvəl işçidən yazılı izahat tələb olunur (imtina tənbehi istisna
 *    etmir) — izahat sübutu yoxdursa xəbərdarlıq.
 *  - Md.189.1: bir xətaya görə yalnız bir tənbeh — işə artıq əmr bağlanıbsa blok.
 *  - Md.189.2: işçi məzuniyyətdə və ya xəstə olduğu müddətdə tənbeh verilə bilməz —
 *    bu gün işdə deyilsə xəbərdarlıq (əmr qaralamadır, imza tarixi sonra ola bilər).
 *
 * Müddət keçibsə töhmət/şiddətli töhmət seçimi bloklanır.
 */
class DisciplinaryDeadline
{
    public const DISCOVERY_MONTHS = 1;

    public const COMMISSION_MONTHS = 6;

    public const AUDIT_COMMISSION_YEARS = 2;

    public function __construct(private readonly EvidenceService $evidence) {}

    /**
     * @return array{blocked: bool, deadline: ?string, discovery_deadline: ?string, commission_deadline: ?string, excluded_days: int, messages: list<array{level: string, code: string, text: string}>}
     */
    public function evaluate(RelationCase $case, ?Carbon $on = null): array
    {
        $on = ($on ?? today())->copy()->startOfDay();
        $result = [
            'blocked' => false,
            'deadline' => null,
            'discovery_deadline' => null,
            'commission_deadline' => null,
            'excluded_days' => 0,
            'messages' => [],
        ];

        $subject = $case->subject_personnel_id !== null
            ? Personnel::withTrashed()->select(['id', 'tabel_no'])->find($case->subject_personnel_id)
            : null;

        if ($subject === null) {
            return $this->block($result, 'subject_required');
        }

        if ($case->order_log_id !== null) {
            return $this->block($result, 'already_sanctioned');
        }

        if ($case->occurred_at === null || $case->discovered_at === null) {
            return $this->block($result, 'dates_required');
        }

        $occurred = $case->occurred_at->copy()->startOfDay();
        $discovered = $case->discovered_at->copy()->startOfDay();

        if ($occurred->gt($discovered)) {
            return $this->block($result, 'dates_inconsistent');
        }

        [$discoveryDeadline, $excluded] = $this->discoveryDeadline($discovered, (string) $subject->tabel_no);
        $commissionDeadline = $case->discovered_by_audit
            ? $occurred->copy()->addYearsNoOverflow(self::AUDIT_COMMISSION_YEARS)
            : $occurred->copy()->addMonthsNoOverflow(self::COMMISSION_MONTHS);

        $deadline = $discoveryDeadline->lt($commissionDeadline) ? $discoveryDeadline : $commissionDeadline;
        $rule = $discoveryDeadline->lt($commissionDeadline) ? 'discovery' : ($case->discovered_by_audit ? 'audit' : 'commission');

        $result['deadline'] = $deadline->toDateString();
        $result['discovery_deadline'] = $discoveryDeadline->toDateString();
        $result['commission_deadline'] = $commissionDeadline->toDateString();
        $result['excluded_days'] = $excluded;

        $params = [
            'date' => $deadline->format('d.m.Y'),
            'days' => $excluded,
            'rule' => __('employee_relations::cases.deadline.rules.'.$rule),
        ];

        if ($on->gt($deadline)) {
            $result['blocked'] = true;
            $result['messages'][] = $this->message('error', 'deadline_passed', $params);
        } elseif ($on->copy()->addDays(max(0, (int) config('employee_relations.deadline_warning_days', 7)))->gte($deadline)) {
            $result['messages'][] = $this->message('warning', 'deadline_near', $params + ['left' => (int) $on->diffInDays($deadline)]);
        } else {
            $result['messages'][] = $this->message('info', 'deadline_info', $params);
        }

        if ($excluded > 0) {
            $result['messages'][] = $this->message('info', 'excluded_days', $params);
        }

        if (! $this->evidence->hasSubjectExplanation($case)) {
            $result['messages'][] = $this->message('warning', 'explanation_missing', []);
        }

        if ($this->absentDays((string) $subject->tabel_no, $on, $on) > 0) {
            $result['messages'][] = $this->message('warning', 'subject_absent', []);
        }

        return $result;
    }

    /**
     * Aşkar edilmə tarixindən bir ay; aradakı məzuniyyət/xəstəlik günləri qədər uzadılır
     * (uzadılmış hissəyə düşən yeni günlər də nəzərə alınsın deyə bir neçə dəfə hesablanır).
     *
     * @return array{0: Carbon, 1: int}
     */
    private function discoveryDeadline(Carbon $discovered, string $tabelNo): array
    {
        $base = $discovered->copy()->addMonthsNoOverflow(self::DISCOVERY_MONTHS);
        $deadline = $base->copy();
        $excluded = 0;

        for ($pass = 0; $pass < 4; $pass++) {
            $excluded = $this->absentDays($tabelNo, $discovered->copy()->addDay(), $deadline);
            $next = $base->copy()->addDays($excluded);

            if ($next->equalTo($deadline)) {
                break;
            }

            $deadline = $next;
        }

        return [$deadline, $excluded];
    }

    /** [from, to] aralığında işçinin bütöv gün məzuniyyət/icazə günlərinin sayı. */
    private function absentDays(string $tabelNo, Carbon $from, Carbon $to): int
    {
        if ($tabelNo === '' || $from->gt($to)) {
            return 0;
        }

        $fromDay = CarbonImmutable::parse($from->toDateString());
        $toDay = CarbonImmutable::parse($to->toDateString());
        $days = [];

        foreach (app()->tagged(AbsenceSource::TAG) as $source) {
            if (! $source instanceof AbsenceSource) {
                continue;
            }

            foreach ($source->overlapping($tabelNo, $fromDay, $toDay) as $period) {
                if (! in_array($period->type, [AbsencePeriod::TYPE_VACATION, AbsencePeriod::TYPE_LEAVE], true) || $period->isPartialDay()) {
                    continue;
                }

                $cursor = $period->from->max($fromDay);
                $end = $period->to->min($toDay);

                while ($cursor->lte($end)) {
                    $days[$cursor->toDateString()] = true;
                    $cursor = $cursor->addDay();
                }
            }
        }

        return count($days);
    }

    /**
     * @param  array{blocked: bool, deadline: ?string, discovery_deadline: ?string, commission_deadline: ?string, excluded_days: int, messages: list<array{level: string, code: string, text: string}>}  $result
     * @return array{blocked: bool, deadline: ?string, discovery_deadline: ?string, commission_deadline: ?string, excluded_days: int, messages: list<array{level: string, code: string, text: string}>}
     */
    private function block(array $result, string $code): array
    {
        $result['blocked'] = true;
        $result['messages'][] = $this->message('error', $code, []);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{level: string, code: string, text: string}
     */
    private function message(string $level, string $code, array $params): array
    {
        return [
            'level' => $level,
            'code' => $code,
            'text' => (string) __('employee_relations::cases.deadline.'.$code, $params),
        ];
    }
}
