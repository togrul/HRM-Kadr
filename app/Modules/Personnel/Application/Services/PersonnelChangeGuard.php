<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Modules\Personnel\Contracts\GuardsPersonnelChanges;
use App\Modules\Personnel\Contracts\PersonnelChangeMode;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Dəyişiklik siyasətini tətbiq edən qoruyucu (bax GuardsPersonnelChanges).
 *
 * Qoruma iki qatdadır: işçi forması məhdud sahəni sahə xətası ilə qaytarır, model
 * səviyyəsində isə (PersonnelObserver::updating) hər Eloquent yazması yoxlanılır — beləliklə
 * hazırlanmış Livewire sorğusu, API, idxal və ya başqa ekran da siyasətdən yan keçə bilmir.
 * Əmr effektləri dəyişikliyi `allowForEffect()` daxilində edir; reyestr hər effektin hansı
 * qrupları yaza biləcəyini müəyyən edir.
 *
 * Yeni əməkdaşın yaradılması (eyni sorğuda yaradılıb dərhal yenilənən model daxil) və
 * hələ təsdiqlənməmiş (is_pending) əməkdaş qorunmur: işə qəbul hələ başa çatmayıb.
 */
class PersonnelChangeGuard implements GuardsPersonnelChanges
{
    public const GROUP_ASSIGNMENT = PersonnelFieldGroupRegistry::ASSIGNMENT;

    /** Jurnala yazılan səbəbin maksimal uzunluğu. */
    public const MAX_REASON_LENGTH = 500;

    /**
     * Açıq icazəli kontekstlər (iç-içə): null — bütün qruplar, siyahı — yalnız onlar.
     *
     * @var list<list<string>|null>
     */
    private array $scopes = [];

    /** @var list<string> */
    private array $reasons = [];

    public function __construct(
        private readonly PersonnelFieldGroupRegistry $registry,
        private readonly PersonnelChangePolicyService $policy,
    ) {}

    public function modeFor(string $group): PersonnelChangeMode
    {
        return $this->policy->modeFor($group);
    }

    public function allow(callable $callback, ?array $groups = null): mixed
    {
        $this->scopes[] = $groups;

        try {
            return $callback();
        } finally {
            array_pop($this->scopes);
        }
    }

    public function allowForEffect(string $effect, callable $callback): mixed
    {
        return $this->allow($callback, $this->registry->groupsForEffect($effect));
    }

    public function isAllowed(?string $group = null): bool
    {
        if ($group === null) {
            return $this->scopes !== [];
        }

        foreach ($this->scopes as $scope) {
            if ($scope === null || in_array($group, $scope, true)) {
                return true;
            }
        }

        return false;
    }

    public function withReason(string $reason, callable $callback): mixed
    {
        $this->reasons[] = trim($reason);

        try {
            return $callback();
        } finally {
            array_pop($this->reasons);
        }
    }

    public function currentReason(): ?string
    {
        $reason = $this->reasons === [] ? '' : (string) end($this->reasons);

        return mb_strlen($reason) >= PersonnelChangeMode::MIN_REASON_LENGTH ? $reason : null;
    }

    /**
     * Sahə qrupu → `personnels` sütunları (sütunsuz qruplar daxil deyil).
     *
     * @return array<string, list<string>>
     */
    public function fieldGroups(): array
    {
        $groups = [];
        foreach ($this->registry->keys() as $group) {
            $columns = $this->registry->columns($group);
            if ($columns !== []) {
                $groups[$group] = $columns;
            }
        }

        return $groups;
    }

    /**
     * Köhnə səth (GuardsPersonnelAssignment): təyinat qrupunun hal-hazırda məhdud sütunları.
     * Bütün qruplar üçün `restrictedAttributes()` işlədilir.
     */
    public function guardedAttributes(): array
    {
        return $this->modeFor(self::GROUP_ASSIGNMENT)->isRestricted()
            ? $this->registry->columns(self::GROUP_ASSIGNMENT)
            : [];
    }

    public function restrictedAttributes(): array
    {
        $attributes = [];
        foreach ($this->registry->columnMap() as $column => $group) {
            if ($this->modeFor($group)->isRestricted()) {
                $attributes[] = $column;
            }
        }

        return $attributes;
    }

    public function isGuarded(string $attribute): bool
    {
        return in_array($attribute, $this->restrictedAttributes(), true);
    }

    public function groupOf(string $attribute): ?string
    {
        return $this->registry->groupOfColumn($attribute);
    }

    public function lockedChanges(Personnel $personnel): array
    {
        return array_keys($this->violations($personnel));
    }

    public function enforce(Personnel $personnel): void
    {
        $violations = $this->violations($personnel);

        if ($violations !== []) {
            throw ValidationException::withMessages($violations);
        }
    }

    public function journal(Personnel $personnel): void
    {
        $reason = $this->currentReason();
        if ($reason === null || $this->isExempt($personnel)) {
            return;
        }

        $byGroup = [];
        foreach (array_keys($personnel->getChanges()) as $attribute) {
            $group = $this->groupOf($attribute);
            if ($group === null || $this->modeFor($group) !== PersonnelChangeMode::Journal || $this->isAllowed($group)) {
                continue;
            }

            $old = $this->normalize($personnel, $attribute, $personnel->getRawOriginal($attribute));
            $new = $this->normalize($personnel, $attribute, $personnel->getAttributes()[$attribute] ?? null);
            if ($old === $new) {
                continue;
            }

            $byGroup[$group][$attribute] = ['old' => $old, 'new' => $new];
        }

        foreach ($byGroup as $group => $changes) {
            $this->recordJournal($group, $reason, $changes, $personnel);
        }
    }

    /**
     * Formdan gələn dəyərlərdən mövcud əməkdaşda dəyişdirilmək istənən, verilən rejimli
     * (ilkin olaraq `order`) qrupların sütunları.
     *
     * @param  array<string, mixed>  $submitted
     * @param  list<PersonnelChangeMode>  $modes
     * @return list<string>
     */
    public function changedInPayload(Personnel $personnel, array $submitted, array $modes = [PersonnelChangeMode::Order]): array
    {
        if (! $personnel->exists || $this->isExempt($personnel)) {
            return [];
        }

        $changed = [];
        foreach ($this->registry->columnMap() as $attribute => $group) {
            if (! array_key_exists($attribute, $submitted)
                || ! in_array($this->modeFor($group), $modes, true)
                || $this->isAllowed($group)) {
                continue;
            }

            $original = $this->normalize($personnel, $attribute, $personnel->getRawOriginal($attribute));
            if ($original !== $this->normalize($personnel, $attribute, $submitted[$attribute])) {
                $changed[] = $attribute;
            }
        }

        return $changed;
    }

    /**
     * Mövcud əməkdaşın saxlanma payload-ından `order` rejimli sütunları çıxarır.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function withoutGuarded(Personnel $personnel, array $payload): array
    {
        if (! $personnel->exists || $this->isExempt($personnel)) {
            return $payload;
        }

        $locked = [];
        foreach ($this->registry->columnMap() as $attribute => $group) {
            if ($this->modeFor($group) === PersonnelChangeMode::Order && ! $this->isAllowed($group)) {
                $locked[] = $attribute;
            }
        }

        return Arr::except($payload, $locked);
    }

    public function authorizeExternalChange(string $group, ?string $reason, array $changes = [], array $context = [], string $errorKey = 'reason', ?Model $subject = null): void
    {
        $mode = $this->modeFor($group);
        if ($mode === PersonnelChangeMode::Free || $this->isAllowed($group)) {
            return;
        }

        if ($mode === PersonnelChangeMode::Order) {
            throw ValidationException::withMessages([$errorKey => $this->registry->orderMessage($group)]);
        }

        $reason = trim((string) $reason);
        if (mb_strlen($reason) < PersonnelChangeMode::MIN_REASON_LENGTH) {
            throw ValidationException::withMessages([$errorKey => $this->reasonRequiredMessage()]);
        }

        $this->recordJournal($group, $reason, $changes, $subject, $context);
    }

    public function recordJournal(string $group, string $reason, array $changes, ?Model $subject = null, array $context = []): void
    {
        $old = [];
        $new = [];
        $redacted = [];
        foreach ($changes as $attribute => $change) {
            if (in_array($attribute, Personnel::ACTIVITY_LOG_EXCLUDED, true)) {
                $redacted[] = $attribute;
                $old[$attribute] = null;
                $new[$attribute] = null;

                continue;
            }

            $old[$attribute] = $change['old'] ?? null;
            $new[$attribute] = $change['new'] ?? null;
        }

        $activity = activity('personnel')
            ->event('change_policy_journal')
            ->withProperties(array_filter([
                'field_group' => $group,
                'reason' => mb_substr(trim($reason), 0, self::MAX_REASON_LENGTH),
                'old' => $old,
                'attributes' => $new,
                'redacted' => $redacted,
                'context' => $context,
            ], static fn (mixed $value): bool => $value !== []));

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        if (auth()->user() !== null) {
            $activity->causedBy(auth()->user());
        }

        $activity->log('personnel.change_policy.journal');
    }

    public function reasonRequiredMessage(): string
    {
        return __('personnel::change_policy.validation.reason_required', ['min' => PersonnelChangeMode::MIN_REASON_LENGTH]);
    }

    /**
     * Saxlanmağa hazırlaşan modeldə siyasətə zidd dəyişmiş sütunlar: sütun → mesaj.
     *
     * @return array<string, string>
     */
    private function violations(Personnel $personnel): array
    {
        if (! $personnel->exists || $this->isExempt($personnel)) {
            return [];
        }

        $violations = [];
        foreach ($this->registry->columnMap() as $attribute => $group) {
            if (! $personnel->isDirty($attribute) || $this->isAllowed($group)) {
                continue;
            }

            $mode = $this->modeFor($group);
            if (! $mode->isRestricted()) {
                continue;
            }

            $original = $this->normalize($personnel, $attribute, $personnel->getRawOriginal($attribute));
            $current = $this->normalize($personnel, $attribute, $personnel->getAttributes()[$attribute] ?? null);
            if ($original === $current) {
                continue;
            }

            if ($mode === PersonnelChangeMode::Order) {
                $violations[$attribute] = $this->registry->orderMessage($group);
            } elseif ($this->currentReason() === null) {
                $violations[$attribute] = $this->reasonRequiredMessage();
            }
        }

        return $violations;
    }

    /**
     * Yeni yaradılan və hələ təsdiqlənməmiş əməkdaş siyasətə tabe deyil.
     */
    private function isExempt(Personnel $personnel): bool
    {
        return $personnel->wasRecentlyCreated || (bool) $personnel->getRawOriginal('is_pending');
    }

    /**
     * Müqayisə üçün vahid forma: boş → null, seçim massivi → id, tarix → Y-m-d.
     */
    private function normalize(Personnel $personnel, string $attribute, mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if ($value === null || $value === '' || ! is_scalar($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (in_array($attribute, (array) $personnel->dateList(), true)) {
            try {
                return Carbon::parse((string) $value)->format('Y-m-d');
            } catch (Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    }
}
