<?php

namespace App\Modules\EmployeeRelations\Domain;

/**
 * İşin mərhələləri: açıldı → araşdırılır → qərar → bağlandı, apellyasiya olarsa
 * qərar → apellyasiya → yenidən baxış → yekun → bağlandı.
 */
enum CaseStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Decided = 'decided';
    case Appealed = 'appealed';
    case AppealReview = 'appeal_review';
    case Final = 'final';
    case Closed = 'closed';

    /**
     * Bütün icazəli keçidlər (mənbə => hədəflər). Keçidlərin bir hissəsi yalnız ayrıca
     * xidmətlə edilir (qərar, apellyasiya, yenidən baxışın nəticəsi) — bax manualTargets().
     *
     * @return array<string, list<string>>
     */
    public static function graph(): array
    {
        return [
            self::Open->value => [self::Investigating->value, self::Closed->value],
            self::Investigating->value => [self::Decided->value],
            self::Decided->value => [self::Appealed->value, self::Closed->value],
            self::Appealed->value => [self::AppealReview->value],
            self::AppealReview->value => [self::Final->value],
            self::Final->value => [self::Closed->value],
            self::Closed->value => [],
        ];
    }

    /**
     * «Mərhələni dəyiş» düyməsi ilə edilə bilən keçidlər. Qərar (DecisionService),
     * apellyasiya (AppealService::submit) və yekun (AppealService::resolve) öz
     * qaydaları ilə gedir.
     *
     * @return list<self>
     */
    public function manualTargets(): array
    {
        return match ($this) {
            self::Open => [self::Investigating, self::Closed],
            self::Decided => [self::Closed],
            self::Appealed => [self::AppealReview],
            self::Final => [self::Closed],
            default => [],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target->value, self::graph()[$this->value] ?? [], true);
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }

    /** Mərhələnin SLA-sı var (qərardan sonrakı apellyasiya pəncərəsi SLA deyil). */
    public function hasSla(): bool
    {
        return ! in_array($this, [self::Decided, self::Closed], true);
    }

    public function label(): string
    {
        return __('employee_relations::cases.statuses.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
