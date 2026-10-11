<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\Orders\Contracts\OrderDrafter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Araşdırmanın qərarı. Töhmət və şiddətli töhmət (ƏM Md.186.2 «a», «b») intizam
 * tənbehidir və işəgötürənin əmri ilə verilir (Md.189.3): qərarla birlikdə Orders
 * modulunun OrderDrafter kontraktı vasitəsilə «disciplinary» təsirli şablondan əmr
 * qaralaması yaradılır və iş ona bağlanır. Əmr adi qaydada /orders-da yoxlanılıb
 * təsdiqlənir; təsdiq zamanı DisciplinaryEffect tənbehi şəxsi işə yazır.
 *
 * Hüquqi müddət keçibsə (Md.187.2–3) tənbeh seçimi rədd edilir.
 */
class DecisionService
{
    /** Standart intizam əmri şablonu (SeedOrderWordTemplatesCommand). */
    public const PREFERRED_TEMPLATE = 'intizam_tenbehi';

    /**
     * Şablonun manual sahələrinin etiketləri. Məlumat açarlarıdır, UI mətni deyil:
     * OrderDrafter onları şablon dəyişənlərinin etiketləri ilə tutuşdurur.
     */
    public const FIELD_VIOLATION = 'Pozuntunun təsviri';

    public const FIELD_SANCTION = 'Tənbehin növü';

    public const FIELD_BASIS = 'Əsas mətni';

    /** Əmrin əsas mətni — sənəd məzmunudur, həmişə Azərbaycan dilində. */
    public const ORDER_BASIS = '%s saylı xidməti araşdırmanın materialları və işçinin izahatı';

    public function __construct(
        private readonly CaseAccess $access,
        private readonly CaseWorkflow $workflow,
        private readonly DisciplinaryDeadline $deadline,
        private readonly CaseNotifier $notifier,
        private readonly CaseAuditLogger $audit,
        private readonly OrderDrafter $orders,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function decide(RelationCase $case, User $actor, DecisionOutcome $outcome, string $summary): RelationCase
    {
        if (! $this->access->canManage($actor, $case)) {
            throw new AuthorizationException;
        }

        if ($case->status !== CaseStatus::Investigating) {
            throw ValidationException::withMessages(['decision' => __('employee_relations::cases.messages.decision_requires_investigation')]);
        }

        $summary = trim($summary);

        if ($summary === '') {
            throw ValidationException::withMessages(['decision_summary' => __('employee_relations::cases.messages.summary_required')]);
        }

        return DB::transaction(function () use ($case, $actor, $outcome, $summary): RelationCase {
            $this->applyOutcome($case, $actor, $outcome, $summary);

            $case->forceFill([
                'appeal_deadline' => today()->addDays(max(1, (int) config('employee_relations.appeal_days', 10)))->toDateString(),
                'decision_notified_at' => now(),
            ])->save();

            $this->workflow->move($case, CaseStatus::Decided, $actor, $outcome->label(), 'decision');
            $this->notifier->decisionAnnounced($case);

            return $case->refresh();
        });
    }

    /**
     * Qərarı işə yazır; tənbehdirsə müddəti yoxlayıb əmr qaralaması yaradır. Yenidən
     * baxışda dəyişdirilmiş qərar da buradan keçir.
     *
     * @throws ValidationException
     */
    public function applyOutcome(RelationCase $case, User $actor, DecisionOutcome $outcome, string $summary): void
    {
        $orderId = $case->order_log_id;

        if ($outcome->isSanction()) {
            $evaluation = $this->deadline->evaluate($case);

            if ($evaluation['blocked']) {
                $errors = array_values(array_filter($evaluation['messages'], fn (array $message): bool => $message['level'] === 'error'));

                throw ValidationException::withMessages(['decision_outcome' => $errors[0]['text'] ?? __('employee_relations::cases.messages.sanction_blocked')]);
            }

            $orderId = $this->draftOrder($case, $outcome, $actor);
        }

        $case->forceFill([
            'decision_outcome' => $outcome,
            'decision_summary' => $summary,
            'decided_at' => now(),
            'decided_by' => $actor->getKey(),
            'order_log_id' => $orderId,
        ])->save();

        $this->audit->log('decided', $case, [
            'outcome' => $outcome->value,
            'order_log_id' => $orderId,
        ], $actor);
    }

    /**
     * Seçilə bilən intizam əmri şablonları (kod => ad). Boşdursa tənbeh qərarı verilə bilməz.
     *
     * @return array<string, string>
     */
    public function disciplinaryTemplates(): array
    {
        try {
            return $this->orders->personnelTemplates('disciplinary');
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @throws ValidationException
     */
    private function draftOrder(RelationCase $case, DecisionOutcome $outcome, User $actor): int
    {
        $templates = $this->disciplinaryTemplates();
        $code = array_key_exists(self::PREFERRED_TEMPLATE, $templates) ? self::PREFERRED_TEMPLATE : array_key_first($templates);

        if ($code === null) {
            throw ValidationException::withMessages(['decision_outcome' => __('employee_relations::cases.messages.template_missing')]);
        }

        /** @var Personnel $subject */
        $subject = Personnel::query()->findOrFail($case->subject_personnel_id);

        try {
            $order = $this->orders->draft($code, $subject, [
                self::FIELD_VIOLATION => $case->title,
                self::FIELD_SANCTION => (string) $outcome->orderSanctionText(),
                self::FIELD_BASIS => sprintf(self::ORDER_BASIS, $case->label()),
            ], $case->label());
        } catch (RuntimeException $exception) {
            report($exception);

            throw ValidationException::withMessages(['decision_outcome' => __('employee_relations::cases.messages.order_draft_failed')]);
        }

        $this->workflow->record($case, 'order_drafted', $order->order_no ?? $case->label(), $actor);

        return (int) $order->getKey();
    }
}
