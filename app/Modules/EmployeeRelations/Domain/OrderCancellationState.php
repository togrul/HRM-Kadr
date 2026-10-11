<?php

namespace App\Modules\EmployeeRelations\Domain;

/**
 * Apellyasiya ilə ləğv edilən qərarın bağlı əmrinin taleyi. Təsdiqlənmiş hüquqi sənəd
 * (əmr) apellyasiya nəticəsi ilə öz-özünə ləğv olunmur: Orders modulunun öz təsdiq
 * axını ilə ləğv edilməlidir. İş bu vəziyyətdə «gözləyir».
 */
enum OrderCancellationState: string
{
    /** Əmr hələ ləğv edilməyib: iş bağlana bilməz. */
    case Pending = 'pending';

    /** Əmr Orders modulunda ləğv edildi (və ya silindi). */
    case Completed = 'completed';

    /** Əmr qüvvədə saxlanılır: səbəb yazılmaqla icazəli şəxs istisna təsdiq etdi. */
    case Waived = 'waived';

    public function label(): string
    {
        return __('employee_relations::cases.order_cancellation.states.'.$this->value);
    }
}
