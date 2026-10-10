<?php

namespace App\Modules\Personnel\Application\Services;

use App\Modules\Personnel\Contracts\PersonnelChangeMode;

/**
 * İşçi sahə qruplarının reyestri: hər qrupa düşən `personnels` sütunları, işçi formasının
 * əlaqə payload-ları (sənədlər, ailə), qrupu qanuni yaza bilən əmr effektləri və
 * siyasət yazılmayanda işləyən ilkin rejim.
 *
 * Siyasət (PersonnelChangePolicyService) yalnız rejimi saxlayır; qrupun nəyi əhatə etdiyi
 * və kimin onu yaza biləcəyi buradadır. «salary» qrupunun sahibi Compensation modulıdur:
 * onun sütunu yoxdur, yoxlamanı Compensation özü GuardsPersonnelChanges ilə çağırır.
 */
class PersonnelFieldGroupRegistry
{
    public const ASSIGNMENT = 'assignment';

    public const SALARY = 'salary';

    public const SURNAME = 'surname';

    public const EMPLOYMENT_DATES = 'employment_dates';

    public const CONTACT = 'contact';

    public const FAMILY = 'family';

    public const DOCUMENTS = 'documents';

    public const PHOTO_NOTES = 'photo_notes';

    public const OWNER_PERSONNEL = 'personnel';

    public const OWNER_COMPENSATION = 'compensation';

    /**
     * @var array<string, array{
     *     columns: list<string>,
     *     relations: list<string>,
     *     wizard_step: int|null,
     *     effects: list<string>,
     *     default: PersonnelChangeMode,
     *     owner: string,
     *     order_effect: string|null,
     *     order_message: string|null
     * }>
     */
    private array $groups;

    public function __construct()
    {
        $this->groups = [
            self::ASSIGNMENT => [
                'columns' => ['structure_id', 'position_id'],
                'relations' => [],
                'wizard_step' => 1,
                'effects' => ['transfer', 'hire'],
                'default' => PersonnelChangeMode::Order,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => 'transfer',
                'order_message' => 'personnel::common.validation.assignment_order_only',
            ],
            self::SALARY => [
                'columns' => [],
                'relations' => [],
                'wizard_step' => null,
                'effects' => ['salary_change', 'hire'],
                'default' => PersonnelChangeMode::Order,
                'owner' => self::OWNER_COMPENSATION,
                'order_effect' => 'salary_change',
                'order_message' => null,
            ],
            self::SURNAME => [
                'columns' => ['surname'],
                'relations' => [],
                'wizard_step' => 1,
                'effects' => ['surname_change'],
                'default' => PersonnelChangeMode::Order,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => 'surname_change',
                'order_message' => null,
            ],
            self::EMPLOYMENT_DATES => [
                'columns' => ['join_work_date', 'leave_work_date'],
                'relations' => [],
                'wizard_step' => 1,
                'effects' => ['hire', 'termination'],
                // No order type corrects a mistyped hire/termination date, so by default it is
                // editable with a stated reason (audited) rather than locked behind an order.
                'default' => PersonnelChangeMode::Journal,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => null,
                'order_message' => null,
            ],
            self::CONTACT => [
                'columns' => ['phone', 'mobile', 'email', 'residental_address', 'registered_address'],
                'relations' => [],
                'wizard_step' => 1,
                'effects' => [],
                'default' => PersonnelChangeMode::Free,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => null,
                'order_message' => null,
            ],
            self::FAMILY => [
                'columns' => [],
                'relations' => ['kinships'],
                'wizard_step' => 7,
                'effects' => [],
                'default' => PersonnelChangeMode::Free,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => null,
                'order_message' => null,
            ],
            self::DOCUMENTS => [
                'columns' => [],
                'relations' => ['document', 'service_cards', 'passports'],
                'wizard_step' => 2,
                'effects' => [],
                'default' => PersonnelChangeMode::Free,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => null,
                'order_message' => null,
            ],
            self::PHOTO_NOTES => [
                'columns' => ['photo', 'extra_important_information'],
                'relations' => [],
                'wizard_step' => 1,
                'effects' => [],
                'default' => PersonnelChangeMode::Free,
                'owner' => self::OWNER_PERSONNEL,
                'order_effect' => null,
                'order_message' => null,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->groups);
    }

    public function has(string $group): bool
    {
        return isset($this->groups[$group]);
    }

    /**
     * @return list<string>
     */
    public function columns(string $group): array
    {
        return $this->groups[$group]['columns'] ?? [];
    }

    /**
     * Qrupa düşən işçi forması əlaqə payload-larının açarları (PersonnelFormAssembler).
     *
     * @return list<string>
     */
    public function relations(string $group): array
    {
        return $this->groups[$group]['relations'] ?? [];
    }

    public function wizardStep(string $group): ?int
    {
        return $this->groups[$group]['wizard_step'] ?? null;
    }

    /**
     * Qrupu `order` rejimində də yaza bilən əmr effektləri.
     *
     * @return list<string>
     */
    public function effects(string $group): array
    {
        return $this->groups[$group]['effects'] ?? [];
    }

    public function defaultMode(string $group): PersonnelChangeMode
    {
        return $this->groups[$group]['default'] ?? PersonnelChangeMode::Free;
    }

    public function owner(string $group): string
    {
        return $this->groups[$group]['owner'] ?? self::OWNER_PERSONNEL;
    }

    /** «Əmr yarat» keçidinin açdığı əmr növünün effekti. */
    public function orderEffect(string $group): ?string
    {
        return $this->groups[$group]['order_effect'] ?? null;
    }

    public function label(string $group): string
    {
        return __('personnel::change_policy.groups.'.$group.'.label');
    }

    public function description(string $group): string
    {
        return __('personnel::change_policy.groups.'.$group.'.description');
    }

    /** `order` rejimində rədd mesajı. */
    public function orderMessage(string $group): string
    {
        $key = $this->groups[$group]['order_message'] ?? null;

        return $key !== null
            ? __($key)
            : __('personnel::change_policy.validation.order_only', ['group' => $this->label($group)]);
    }

    /**
     * Effektin yaza bildiyi qruplar.
     *
     * @return list<string>
     */
    public function groupsForEffect(string $effect): array
    {
        return array_values(array_filter(
            $this->keys(),
            fn (string $group): bool => in_array($effect, $this->effects($group), true),
        ));
    }

    public function groupOfColumn(string $column): ?string
    {
        foreach ($this->groups as $group => $definition) {
            if (in_array($column, $definition['columns'], true)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Bütün qrupların sütunları: sütun → qrup.
     *
     * @return array<string, string>
     */
    public function columnMap(): array
    {
        $map = [];
        foreach ($this->groups as $group => $definition) {
            foreach ($definition['columns'] as $column) {
                $map[$column] = $group;
            }
        }

        return $map;
    }
}
