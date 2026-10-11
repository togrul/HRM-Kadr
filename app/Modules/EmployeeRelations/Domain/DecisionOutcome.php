<?php

namespace App\Modules\EmployeeRelations\Domain;

/**
 * Araşdırmanın qərarı.
 *
 * ƏM Md.186.3: şifahi və ya yazılı xəbərdarlıq intizam tənbehi sayılmır. Md.186.2 «a» və
 * «b» bəndləri: töhmət və sonuncu xəbərdarlıqla şiddətli töhmət — intizam tənbehidir və
 * işəgötürənin əmri ilə verilir (Md.189.3), ona görə bu iki qərar intizam əmri qaralaması
 * yaradır. Cərimə (kollektiv müqavilədə nəzərdə tutulubsa) və xitam bu modulda qərar
 * kimi verilmir — xitam ayrıca xitam əmri ilə rəsmiləşdirilir.
 */
enum DecisionOutcome: string
{
    case Unfounded = 'unfounded';
    case VerbalWarning = 'verbal_warning';
    case WrittenWarning = 'written_warning';
    case Reprimand = 'reprimand';
    case SevereReprimand = 'severe_reprimand';

    /** İntizam tənbehidir: əmr qaralaması və Md.187 müddət yoxlaması tələb edir. */
    public function isSanction(): bool
    {
        return in_array($this, [self::Reprimand, self::SevereReprimand], true);
    }

    /** İşçi barəsində tədbir görülüb (şikayətçiyə yalnız bu ümumi nəticə göstərilir). */
    public function isSubstantiated(): bool
    {
        return $this !== self::Unfounded;
    }

    /**
     * Əmr şablonunun «Tənbehin növü» sahəsinə yazılan mətn. Sənəd məzmunudur, UI mətni
     * deyil: əmrlər istifadəçinin dilindən asılı olmayaraq Azərbaycan dilində çıxır.
     */
    public function orderSanctionText(): ?string
    {
        return match ($this) {
            self::Reprimand => 'töhmət',
            self::SevereReprimand => 'sonuncu xəbərdarlıqla şiddətli töhmət',
            default => null,
        };
    }

    public function label(): string
    {
        return __('employee_relations::cases.outcomes.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $outcome): string => $outcome->value, self::cases());
    }
}
