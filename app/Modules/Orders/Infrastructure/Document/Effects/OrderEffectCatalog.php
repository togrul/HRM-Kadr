<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

/**
 * The single source of truth for the HR side-effects an order type can perform on
 * approval: each effect's label, its structured inputs (roles), and its handler class.
 *
 * Drives three things from one definition list, so the handler mapping can never drift
 * from the field structures:
 *   - the designer's effect picker / per-variable role dropdown (options(), roles());
 *   - building the effect's fields at approval (kinds(), isEffect());
 *   - resolving the handler that applies/reverses the effect (for()).
 */
class OrderEffectCatalog
{
    /**
     * Every effect kind with its UI label, structured roles, and handler class.
     * A null handler means the kind is selectable in the designer but its side-effect
     * is run elsewhere (e.g. 'hire' is handled directly by OrderStatusTransitionService).
     *
     * @return array<string,array{label:string,roles:array<int,array{key:string,label:string,type:string}>,handler:?class-string<OrderEffect>}>
     */
    private function definitions(): array
    {
        return [
            'vacation' => [
                'label' => __('orders::order_composer.effects.vacation'),
                'roles' => [
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.vacation_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.vacation_end_date'), 'type' => 'date'],
                    ['key' => 'return_date', 'label' => __('orders::order_composer.effect_roles.vacation_return_date'), 'type' => 'date'],
                    ['key' => 'days', 'label' => __('orders::order_composer.effect_roles.vacation_days'), 'type' => 'number'],
                    ['key' => 'location', 'label' => __('orders::order_composer.effect_roles.vacation_location'), 'type' => 'text'],
                ],
                'handler' => VacationEffect::class,
            ],
            'social_leave' => [
                'label' => __('orders::order_composer.effects.social_leave'),
                'roles' => [
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.vacation_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.vacation_end_date'), 'type' => 'date'],
                    ['key' => 'return_date', 'label' => __('orders::order_composer.effect_roles.vacation_return_date'), 'type' => 'date'],
                    ['key' => 'days', 'label' => __('orders::order_composer.effect_roles.vacation_days'), 'type' => 'number'],
                ],
                'handler' => SocialLeaveEffect::class,
            ],
            'education_leave' => [
                'label' => __('orders::order_composer.effects.education_leave'),
                'roles' => [
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.vacation_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.vacation_end_date'), 'type' => 'date'],
                    ['key' => 'return_date', 'label' => __('orders::order_composer.effect_roles.vacation_return_date'), 'type' => 'date'],
                    ['key' => 'days', 'label' => __('orders::order_composer.effect_roles.vacation_days'), 'type' => 'number'],
                ],
                'handler' => EducationLeaveEffect::class,
            ],
            'unpaid_leave' => [
                'label' => __('orders::order_composer.effects.unpaid_leave'),
                'roles' => [
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.vacation_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.vacation_end_date'), 'type' => 'date'],
                    ['key' => 'return_date', 'label' => __('orders::order_composer.effect_roles.vacation_return_date'), 'type' => 'date'],
                    ['key' => 'days', 'label' => __('orders::order_composer.effect_roles.vacation_days'), 'type' => 'number'],
                ],
                'handler' => UnpaidLeaveEffect::class,
            ],
            'business_trip' => [
                'label' => __('orders::order_composer.effects.business_trip'),
                'roles' => [
                    ['key' => 'location', 'label' => __('orders::order_composer.effect_roles.business_trip_location'), 'type' => 'text'],
                    ['key' => 'purpose', 'label' => __('orders::order_composer.effect_roles.business_trip_purpose'), 'type' => 'text'],
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.business_trip_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.business_trip_end_date'), 'type' => 'date'],
                    ['key' => 'return_date', 'label' => __('orders::order_composer.effect_roles.business_trip_return_date'), 'type' => 'date'],
                    ['key' => 'transport', 'label' => __('orders::order_composer.effect_roles.business_trip_transport'), 'type' => 'text'],
                    ['key' => 'per_diem', 'label' => __('orders::order_composer.effect_roles.business_trip_per_diem'), 'type' => 'text'],
                    ['key' => 'trip_type', 'label' => __('orders::order_composer.effect_roles.business_trip_trip_type'), 'type' => 'trip_type'],
                    ['key' => 'funding_source', 'label' => __('orders::order_composer.effect_roles.business_trip_funding_source'), 'type' => 'text'],
                ],
                'handler' => BusinessTripEffect::class,
            ],
            'termination' => [
                'label' => __('orders::order_composer.effects.termination'),
                'roles' => [
                    ['key' => 'date', 'label' => __('orders::order_composer.effect_roles.termination_date'), 'type' => 'date'],
                ],
                'handler' => TerminationEffect::class,
            ],
            'transfer' => [
                'label' => __('orders::order_composer.effects.transfer'),
                'roles' => [
                    ['key' => 'new_structure', 'label' => __('orders::order_composer.effect_roles.transfer_new_structure'), 'type' => 'structure'],
                    ['key' => 'new_position', 'label' => __('orders::order_composer.effect_roles.transfer_new_position'), 'type' => 'position'],
                ],
                'handler' => TransferEffect::class,
            ],
            'surname_change' => [
                'label' => __('orders::order_composer.effects.surname_change'),
                'roles' => [
                    ['key' => 'new_surname', 'label' => __('orders::order_composer.effect_roles.surname_change_new_surname'), 'type' => 'text'],
                ],
                'handler' => SurnameChangeEffect::class,
            ],
            'award' => [
                'label' => __('orders::order_composer.effects.award'),
                'roles' => [
                    ['key' => 'amount', 'label' => __('orders::order_composer.effect_roles.award_amount'), 'type' => 'number'],
                    ['key' => 'reason', 'label' => __('orders::order_composer.effect_roles.award_reason'), 'type' => 'text'],
                ],
                'handler' => AwardEffect::class,
            ],
            'paid_absence' => [
                'label' => __('orders::order_composer.effects.paid_absence'),
                'roles' => [
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.paid_absence_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.paid_absence_end_date'), 'type' => 'date'],
                    ['key' => 'days', 'label' => __('orders::order_composer.effect_roles.vacation_days'), 'type' => 'number'],
                    ['key' => 'reason', 'label' => __('orders::order_composer.effect_roles.paid_absence_reason'), 'type' => 'text'],
                ],
                'handler' => PaidAbsenceEffect::class,
            ],
            'disciplinary' => [
                'label' => __('orders::order_composer.effects.disciplinary'),
                'roles' => [
                    ['key' => 'sanction_type', 'label' => __('orders::order_composer.effect_roles.disciplinary_sanction_type'), 'type' => 'text'],
                    ['key' => 'violation', 'label' => __('orders::order_composer.effect_roles.disciplinary_violation'), 'type' => 'text'],
                    ['key' => 'date', 'label' => __('orders::order_composer.effect_roles.disciplinary_date'), 'type' => 'date'],
                ],
                'handler' => DisciplinaryEffect::class,
            ],
            'salary_change' => [
                'label' => __('orders::order_composer.effects.salary_change'),
                'roles' => [
                    ['key' => 'new_salary', 'label' => __('orders::order_composer.effect_roles.salary_change_new_salary'), 'type' => 'number'],
                    ['key' => 'effective_date', 'label' => __('orders::order_composer.effect_roles.salary_change_effective_date'), 'type' => 'date'],
                ],
                'handler' => SalaryChangeEffect::class,
            ],
            'vacation_recall' => [
                'label' => __('orders::order_composer.effects.vacation_recall'),
                'roles' => [
                    ['key' => 'recall_date', 'label' => __('orders::order_composer.effect_roles.vacation_recall_date'), 'type' => 'date'],
                ],
                'handler' => VacationRecallEffect::class,
            ],
            'vacation_compensation' => [
                'label' => __('orders::order_composer.effects.vacation_compensation'),
                'roles' => [
                    ['key' => 'days', 'label' => __('orders::order_composer.effect_roles.vacation_days'), 'type' => 'number'],
                    ['key' => 'work_year', 'label' => __('orders::order_composer.effect_roles.vacation_compensation_work_year'), 'type' => 'date'],
                    ['key' => 'amount', 'label' => __('orders::order_composer.effect_roles.award_amount'), 'type' => 'number'],
                ],
                'handler' => VacationCompensationEffect::class,
            ],
            'non_working_day_work' => [
                'label' => __('orders::order_composer.effects.non_working_day_work'),
                'roles' => [
                    ['key' => 'work_date', 'label' => __('orders::order_composer.effect_roles.non_working_day_work_date'), 'type' => 'date'],
                    ['key' => 'compensation', 'label' => __('orders::order_composer.effect_roles.non_working_day_work_compensation'), 'type' => 'rest_day_compensation'],
                ],
                'handler' => NonWorkingDayWorkEffect::class,
            ],
            'substitution' => [
                'label' => __('orders::order_composer.effects.substitution'),
                'roles' => [
                    ['key' => 'substituted_employee', 'label' => __('orders::order_composer.effect_roles.substitution_employee'), 'type' => 'personnel'],
                    ['key' => 'substituted_position', 'label' => __('orders::order_composer.effect_roles.substitution_position'), 'type' => 'position'],
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.vacation_start_date'), 'type' => 'date'],
                    ['key' => 'end_date', 'label' => __('orders::order_composer.effect_roles.vacation_end_date'), 'type' => 'date'],
                    ['key' => 'extra_pay_percent', 'label' => __('orders::order_composer.effect_roles.substitution_extra_pay_percent'), 'type' => 'number'],
                    ['key' => 'extra_pay_amount', 'label' => __('orders::order_composer.effect_roles.substitution_extra_pay_amount'), 'type' => 'number'],
                ],
                'handler' => SubstitutionEffect::class,
            ],
            'order_cancellation' => [
                'label' => __('orders::order_composer.effects.order_cancellation'),
                'roles' => [
                    ['key' => 'target_order', 'label' => __('orders::order_composer.effect_roles.order_cancellation_target'), 'type' => 'approved_order'],
                ],
                'handler' => OrderCancellationEffect::class,
            ],
            'hire' => [
                'label' => __('orders::order_composer.effects.hire'),
                // Structure & position are dedicated hire inputs (they also drive the
                // document's employee.* variables); only the join date is a role here.
                // Hire has no OrderEffect handler — OrderStatusTransitionService converts
                // the candidate into an employee directly.
                'roles' => [
                    ['key' => 'start_date', 'label' => __('orders::order_composer.effect_roles.hire_start_date'), 'type' => 'date'],
                ],
                'handler' => null,
            ],
        ];
    }

    /**
     * Effects that make sense once per person, so a template on them may be issued for several
     * employees at once (çoxşəxsli): each participant gets their own record, all in one
     * approval. Effects that move, rename, pay or end one employee's contract stay single.
     */
    private const MULTI_PARTICIPANT = [
        'business_trip',
        'paid_absence',
        'award',
        'vacation',
        'social_leave',
        'education_leave',
        'unpaid_leave',
        'disciplinary',
    ];

    /** True when a template on this effect may be marked multi-participant ('none' — document only — may). */
    public function supportsParticipants(string $kind): bool
    {
        return $kind === 'none' || in_array($kind, self::MULTI_PARTICIPANT, true);
    }

    /**
     * @return array<string,array{label:string,roles:array<int,array{key:string,label:string,type:string}>}>
     */
    public function kinds(): array
    {
        return array_map(
            fn (array $def) => ['label' => $def['label'], 'roles' => $def['roles']],
            $this->definitions(),
        );
    }

    /**
     * Resolve the handler implementing an effect kind. Unmapped/none/handler-less kinds
     * return null (the order just changes status, with no OrderEffect side-effect).
     */
    public function for(string $effect): ?OrderEffect
    {
        $handler = $this->definitions()[$effect]['handler'] ?? null;

        return $handler ? app($handler) : null;
    }

    /**
     * Effect options for the designer selector (with the "no effect" default first).
     *
     * @return array<int,array{kind:string,label:string}>
     */
    public function options(): array
    {
        $options = [['kind' => 'none', 'label' => __('orders::order_composer.effects.none')]];
        foreach ($this->definitions() as $kind => $def) {
            $options[] = ['kind' => $kind, 'label' => $def['label']];
        }

        return $options;
    }

    /**
     * @return array<int,array{key:string,label:string,type:string}>
     */
    public function roles(string $kind): array
    {
        return $this->definitions()[$kind]['roles'] ?? [];
    }

    public function isEffect(string $kind): bool
    {
        return $kind === 'none' || isset($this->definitions()[$kind]);
    }
}
