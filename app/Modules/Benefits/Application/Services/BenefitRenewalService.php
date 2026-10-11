<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\BenefitRenewalWindow;
use App\Models\Personnel;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\UserPersonnelLinkResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * İllik yenilənmə. Paketin müqavilə dövrü bitməzdən `renewal_window_days` gün əvvəl seçim
 * pəncərəsi açılır (scheduler və ya HR əl ilə), qoşulmuş işçilərə bildiriş gedir və onlar
 * Şəxsi kabinetdə paketi yeniləyir, imtina edir və ya eyni növdən başqa paketə keçir.
 *
 * Pəncərə müqavilənin son günündən sonra bağlanır: «yenilə» (və ya cavab verilməyibsə, paket
 * avtomatik yenilənirsə) seçənlər üçün köhnə qoşulma müqavilənin son günü bitir, növbəti gündən
 * paketin CARİ qiymətləri ilə yeni qoşulma yaranır (ailə üzvləri köçürülür); imtina edənlərin
 * qoşulması bitir. Paketin müqavilə dövrü növbəti ilə keçir.
 */
class BenefitRenewalService
{
    public function __construct(
        private readonly BenefitEnrollmentService $enrollments,
        private readonly BenefitEligibilityService $eligibility,
        private readonly BenefitPayrollSync $payroll,
    ) {}

    /**
     * Vaxtı çatmış paketlər üçün pəncərə açır.
     *
     * @return int açılan pəncərə sayı
     */
    public function openDueWindows(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $opened = 0;

        BenefitPlan::query()
            ->where('is_active', true)
            ->whereNotNull('contract_end')
            ->whereDate('contract_end', '>=', $today->toDateString())
            ->get()
            ->each(function (BenefitPlan $plan) use ($today, &$opened): void {
                $end = CarbonImmutable::parse($plan->contract_end);

                if ($today->lt($end->subDays(max(1, (int) $plan->renewal_window_days)))) {
                    return;
                }

                if ($this->openWindow($plan, $today, false) !== null) {
                    $opened++;
                }
            });

        return $opened;
    }

    /**
     * Pəncərəni açır (eyni müqavilə dövrü üçün ikinci dəfə açılmır). $manual — HR düyməsi.
     *
     * @throws ValidationException
     */
    public function openWindow(BenefitPlan $plan, ?CarbonImmutable $today = null, bool $manual = true): ?BenefitRenewalWindow
    {
        $today ??= CarbonImmutable::today();

        if ($plan->contract_end === null) {
            if ($manual) {
                throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.no_contract_end')]);
            }

            return null;
        }

        $end = CarbonImmutable::parse($plan->contract_end);

        if (BenefitRenewalWindow::query()->where('benefit_plan_id', $plan->id)->whereDate('contract_end', $end->toDateString())->exists()) {
            if ($manual) {
                throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.window_exists')]);
            }

            return null;
        }

        $window = DB::transaction(function () use ($plan, $end, $today): BenefitRenewalWindow {
            $window = BenefitRenewalWindow::query()->create([
                'benefit_plan_id' => $plan->id,
                'contract_end' => $end->toDateString(),
                'next_contract_end' => $end->addYear()->toDateString(),
                'opens_on' => $today->toDateString(),
                'closes_on' => $end->toDateString(),
                'status' => BenefitRenewalWindow::STATUS_OPEN,
            ]);

            $this->renewable($plan, $end)->update(['renewal_window_id' => $window->id, 'renewal_decision' => null]);

            return $window;
        });

        $this->notify($window);

        return $window;
    }

    /**
     * Son günü keçmiş pəncərələri bağlayır və qərarları tətbiq edir.
     */
    public function closeDueWindows(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $closed = 0;

        BenefitRenewalWindow::query()
            ->where('status', BenefitRenewalWindow::STATUS_OPEN)
            ->whereDate('closes_on', '<', $today->toDateString())
            ->with('plan')
            ->get()
            ->each(function (BenefitRenewalWindow $window) use ($today, &$closed): void {
                $this->close($window, $today);
                $closed++;
            });

        return $closed;
    }

    /**
     * Pəncərəni bağlayır: hər qoşulmanın qərarı tətbiq olunur, paketin dövrü sürüşür.
     *
     * @return array{renewed: int, ended: int}
     */
    public function close(BenefitRenewalWindow $window, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $plan = $window->plan ?? BenefitPlan::query()->findOrFail($window->benefit_plan_id);
        $end = CarbonImmutable::parse($window->contract_end);
        $nextStart = $end->addDay();
        $result = ['renewed' => 0, 'ended' => 0];
        $tabelNos = [];

        foreach ($this->renewable($plan, $end)->with('dependents')->get() as $enrollment) {
            $decision = $enrollment->renewal_decision ?? ($plan->auto_renew ? 'renew' : 'opt_out');
            $personnel = Personnel::query()->where('tabel_no', $enrollment->tabel_no)->first();
            $stillEligible = $personnel !== null && $plan->is_active && $this->eligibility->eligible($plan, $personnel, $nextStart);

            $enrollment->update([
                'end_date' => $end->toDateString(),
                'end_reason' => $decision === 'renew' && $stillEligible ? 'renewal' : 'opt_out',
                'status' => $end->lt($today) ? BenefitEnrollment::STATUS_ENDED : BenefitEnrollment::STATUS_ACTIVE,
                'renewal_decision' => $decision,
                'renewal_window_id' => $window->id,
            ]);

            $tabelNos[$enrollment->tabel_no] = true;

            if ($decision !== 'renew' || ! $stillEligible) {
                $result['ended']++;

                continue;
            }

            try {
                $this->enrollments->enroll(
                    $plan,
                    $enrollment->tabel_no,
                    $nextStart->toDateString(),
                    $enrollment->dependents->pluck('personnel_kinship_id')->filter()->map(fn ($id): int => (int) $id)->values()->all(),
                    BenefitEnrollmentService::SOURCE_RENEWAL,
                    false,
                    null,
                    (int) $enrollment->id,
                );
                $result['renewed']++;
            } catch (ValidationException $exception) {
                // Məs. ailə üzvü qeydi silinib və ya işçi artıq başqa paketə keçib — qoşulma bitir.
                $enrollment->update(['end_reason' => 'opt_out']);
                $result['ended']++;
            }
        }

        DB::transaction(function () use ($window, $plan, $nextStart): void {
            $window->update(['status' => BenefitRenewalWindow::STATUS_CLOSED, 'closed_at' => now()]);
            $plan->update([
                'contract_start' => $nextStart->toDateString(),
                'contract_end' => CarbonImmutable::parse($window->next_contract_end)->toDateString(),
            ]);
        });

        foreach (array_keys($tabelNos) as $tabelNo) {
            $this->payroll->syncEmployee((string) $tabelNo);
        }

        activity('benefits')
            ->performedOn($window)
            ->causedBy(auth()->user())
            ->withProperties($result)
            ->event('benefits_renewal_closed')
            ->log('benefits_renewal_closed');

        return $result;
    }

    /**
     * Müqavilə dövrünün sonunda hələ qüvvədə olan qoşulmalar.
     *
     * @return \Illuminate\Database\Eloquent\Builder<BenefitEnrollment>
     */
    private function renewable(BenefitPlan $plan, CarbonImmutable $end)
    {
        return BenefitEnrollment::query()
            ->where('benefit_plan_id', $plan->id)
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $end->toDateString()));
    }

    /**
     * Qoşulmuş işçilərin hesablarına bildiriş (Şəxsi kabinetdə seçim üçün).
     */
    private function notify(BenefitRenewalWindow $window): void
    {
        $plan = $window->plan ?? BenefitPlan::query()->find($window->benefit_plan_id);
        $personnelIds = Personnel::query()
            ->whereIn('tabel_no', BenefitEnrollment::query()->where('renewal_window_id', $window->id)->select('tabel_no'))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $userIds = collect(app(UserPersonnelLinkResolver::class)->userIdsByPersonnel($personnelIds))->flatten()->unique()->values()->all();
        $subject = __('benefits::dashboard.renewal.notification_subject', ['plan' => (string) $plan?->name]);
        $body = __('benefits::dashboard.renewal.notification_body', [
            'plan' => (string) $plan?->name,
            'date' => CarbonImmutable::parse($window->closes_on)->format('d.m.Y'),
        ]);

        User::query()->whereIn('id', $userIds)->get()->each(function (User $user) use ($subject, $body): void {
            try {
                $user->notify(new PlatformNotification('database', [
                    'action' => 'benefitsRenewal',
                    'category' => __('benefits::dashboard.title'),
                    'message' => $subject,
                    'name' => $subject,
                    'body' => $body,
                ], $subject, $body));
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        $window->update(['notified_at' => now()]);
    }
}
