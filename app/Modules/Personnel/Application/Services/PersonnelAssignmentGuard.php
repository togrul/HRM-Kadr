<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Modules\Personnel\Contracts\GuardsPersonnelAssignment;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Mövcud əməkdaşın struktur bölməsi və vəzifəsi yalnız əmrlə dəyişir.
 *
 * Qoruma iki qatdadır: redaktə formu fərqli dəyəri sahə xətası kimi qaytarır, model
 * səviyyəsində isə (PersonnelObserver::updating) hər Eloquent yazması yoxlanılır — beləliklə
 * hazırlanmış Livewire sorğusu, API, idxal və ya başqa ekran da sahəni dəyişə bilmir.
 * Qanuni yazıcılar (TransferEffect, işə qəbul) dəyişikliyi `allow()` daxilində edir.
 *
 * Yeni əməkdaşın yaradılması (eyni sorğuda yaradılıb dərhal yenilənən model daxil) qorunmur.
 *
 * Sahə qrupları reyestri gələcək "dəyişiklik siyasəti" (sərbəst / jurnal / əmr tələb olunur)
 * üçün nəzərdə tutulub: siyasət yalnız `fieldGroups()`-un qaytardığını dəyişəcək.
 */
class PersonnelAssignmentGuard implements GuardsPersonnelAssignment
{
    public const GROUP_ASSIGNMENT = 'assignment';

    /**
     * Sahə qrupu → yalnız əmrlə dəyişən atributlar.
     *
     * @var array<string, list<string>>
     */
    public const FIELD_GROUPS = [
        self::GROUP_ASSIGNMENT => ['structure_id', 'position_id'],
    ];

    private int $depth = 0;

    public function allow(callable $callback): mixed
    {
        $this->depth++;

        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }

    public function isAllowed(): bool
    {
        return $this->depth > 0;
    }

    /**
     * Hal-hazırda əmrlə idarə olunan sahə qrupları.
     *
     * @return array<string, list<string>>
     */
    public function fieldGroups(): array
    {
        return self::FIELD_GROUPS;
    }

    public function guardedAttributes(): array
    {
        return array_values(array_unique(Arr::flatten($this->fieldGroups())));
    }

    public function isGuarded(string $attribute): bool
    {
        return in_array($attribute, $this->guardedAttributes(), true);
    }

    public function lockedChanges(Personnel $personnel): array
    {
        if ($this->isAllowed() || ! $personnel->exists || $personnel->wasRecentlyCreated) {
            return [];
        }

        return array_values(array_filter(
            $this->guardedAttributes(),
            fn (string $attribute): bool => $personnel->isDirty($attribute)
                && ! $this->sameValue($personnel->getOriginal($attribute), $personnel->getAttribute($attribute)),
        ));
    }

    public function enforce(Personnel $personnel): void
    {
        $locked = $this->lockedChanges($personnel);

        if ($locked === []) {
            return;
        }

        throw ValidationException::withMessages(
            array_fill_keys($locked, __('personnel::common.validation.assignment_order_only'))
        );
    }

    /**
     * Formdan gələn dəyərlərdən mövcud əməkdaşda dəyişdirilmək istənən qorunan sahələr.
     *
     * @param  array<string, mixed>  $submitted
     * @return list<string>
     */
    public function changedInPayload(Personnel $personnel, array $submitted): array
    {
        if ($this->isAllowed() || ! $personnel->exists) {
            return [];
        }

        return array_values(array_filter(
            $this->guardedAttributes(),
            fn (string $attribute): bool => array_key_exists($attribute, $submitted)
                && ! $this->sameValue($personnel->getRawOriginal($attribute), $submitted[$attribute]),
        ));
    }

    /**
     * Mövcud əməkdaşın saxlanma payload-ından qorunan sahələri çıxarır.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function withoutGuarded(Personnel $personnel, array $payload): array
    {
        if ($this->isAllowed() || ! $personnel->exists) {
            return $payload;
        }

        return Arr::except($payload, $this->guardedAttributes());
    }

    private function sameValue(mixed $original, mixed $current): bool
    {
        $normalize = static function (mixed $value): ?string {
            if (is_array($value)) {
                $value = $value['id'] ?? null;
            }

            return $value === null || $value === '' || ! is_scalar($value) ? null : (string) $value;
        };

        return $normalize($original) === $normalize($current);
    }
}
