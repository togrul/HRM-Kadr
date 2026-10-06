<?php

namespace App\Modules\Integration\Support;

/**
 * The wire contract this module speaks.
 *
 * Kept deliberately in step with the finance side's `App\Support\Integration\Feed`;
 * `docs/11_INTEQRASIYA.md` in that repository is the shared specification and any
 * change starts there, not here.
 *
 * ## Versioning rule
 *
 * **Minor = additive only.** A new field or feed; an older counterpart simply
 * ignores what it does not recognise and keeps working.
 *
 * **Major = a field removed, or its meaning changed.** The counterpart must
 * refuse to connect until both sides are upgraded. Running on a half-understood
 * contract is worse than not running: wrong data would flow silently.
 */
final class Contract
{
    public const VERSION = '1.0';

    public const SYSTEM = 'HRM';

    // Feeds we serve.
    public const ORG_UNITS = 'org.units';

    public const ORG_POSITIONS = 'org.positions';

    public const EMPLOYEES = 'employees';

    public const ORDERS = 'orders';

    public const ATTENDANCE_MONTH = 'attendance.month';

    public const COMPENSATION = 'compensation';

    public const LEAVE_BALANCE = 'leave.balance';

    // Abilities. Granted per feed, never as one blanket token: an attendance
    // consumer has no business reading salaries.
    public const ABILITY_ORG = 'hr.org:read';

    public const ABILITY_EMPLOYEES = 'hr.employees:read';

    public const ABILITY_ORDERS = 'hr.orders:read';

    public const ABILITY_ATTENDANCE = 'hr.attendance:read';

    public const ABILITY_COMPENSATION = 'hr.compensation:read';

    public const ABILITY_LEAVE = 'hr.leave:read';

    /** @return list<string> */
    public static function feeds(): array
    {
        return [self::ORG_UNITS, self::ORG_POSITIONS, self::EMPLOYEES, self::ORDERS, self::ATTENDANCE_MONTH, self::COMPENSATION, self::LEAVE_BALANCE];
    }

    /**
     * Every ability this contract defines.
     *
     * Kept next to `feeds()` on purpose. The token command lists these for the
     * operator, and listing them by hand went stale the moment feeds were
     * added: a scoped token issued from a short list quietly lacks half the
     * feeds, and the failure only shows up later as a 403 with no clear cause.
     *
     * @return list<string>
     */
    public static function abilities(): array
    {
        return [
            self::ABILITY_ORG,
            self::ABILITY_EMPLOYEES,
            self::ABILITY_ORDERS,
            self::ABILITY_ATTENDANCE,
            self::ABILITY_COMPENSATION,
            self::ABILITY_LEAVE,
        ];
    }

    /** Default page size — large enough for a first load, small enough to stream. */
    public const DEFAULT_LIMIT = 500;

    public const MAX_LIMIT = 1000;
}
