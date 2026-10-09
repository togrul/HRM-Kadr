<?php

namespace App\Modules\Personnel\Support\Presence;

/**
 * Where an employee is on a given day. One person has exactly one status; when several
 * facts hold at once the case listed first wins (see precedence()).
 *
 * Precedence, strongest first:
 *  deleted > dismissed > pending > sick > vacation > business_trip > leave > at_work
 *
 * - deleted: the record is soft-deleted;
 * - dismissed: a leave_work_date is set (the "İşdən ayrılan" list uses the same rule);
 * - pending: the hire still waits for approval;
 * - sick: an approved full-day leave of a sick-leave type covers the day;
 * - vacation: an approved (or HR-entered) vacation covers the day;
 * - business_trip: an approved (or HR-entered) business trip covers the day;
 * - leave: any other approved full-day leave covers the day;
 * - at_work: none of the above. Hourly / half-day permissions never change the status.
 */
enum PersonnelPresenceStatus: string
{
    case Deleted = 'deleted';
    case Dismissed = 'dismissed';
    case Pending = 'pending';
    case Sick = 'sick';
    case Vacation = 'vacation';
    case BusinessTrip = 'business_trip';
    case Leave = 'leave';
    case AtWork = 'at_work';

    /**
     * Strongest first; the resolver and the list filter both walk this order.
     *
     * @return list<self>
     */
    public static function precedence(): array
    {
        return [
            self::Deleted,
            self::Dismissed,
            self::Pending,
            self::Sick,
            self::Vacation,
            self::BusinessTrip,
            self::Leave,
            self::AtWork,
        ];
    }

    /**
     * The statuses the employee list can filter by (deleted rows live in their own tab).
     *
     * @return list<self>
     */
    public static function filterable(): array
    {
        return array_values(array_filter(self::precedence(), fn (self $status): bool => $status !== self::Deleted));
    }

    /**
     * Statuses that mean "employed, but not at work today".
     *
     * @return list<self>
     */
    public static function absences(): array
    {
        return [self::Sick, self::Vacation, self::BusinessTrip, self::Leave];
    }

    public function isAbsence(): bool
    {
        return in_array($this, self::absences(), true);
    }

    public function label(): string
    {
        return __('personnel::common.presence.statuses.'.$this->value);
    }

    /** Badge / avatar tone, matching the small-badge palette. */
    public function tone(): string
    {
        return match ($this) {
            self::Deleted, self::Dismissed => 'rose',
            self::Pending => 'amber',
            self::Sick => 'teal',
            self::Vacation => 'violet',
            self::BusinessTrip => 'blue',
            self::Leave => 'indigo',
            self::AtWork => 'green',
        };
    }
}
