<?php

namespace App\Modules\Personnel\Contracts;

/**
 * İşçi sahə qrupunun dəyişiklik rejimi.
 *
 *  - `Free`    — sərbəst: heç bir məhdudiyyət yoxdur;
 *  - `Journal` — sərbəst, amma səbəb (ən azı 5 simvol) mütləqdir və köhnə → yeni dəyərlər
 *                activity log-a yazılır;
 *  - `Order`   — yalnız əmrlə: əmr effektindən (allow() konteksti) kənar yazma rədd olunur.
 */
enum PersonnelChangeMode: string
{
    case Free = 'free';
    case Journal = 'journal';
    case Order = 'order';

    /** Jurnal rejimində səbəbin minimum uzunluğu. */
    public const MIN_REASON_LENGTH = 5;

    public function label(): string
    {
        return __('personnel::change_policy.modes.'.$this->value);
    }

    public function isRestricted(): bool
    {
        return $this !== self::Free;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $mode): string => $mode->value, self::cases());
    }
}
