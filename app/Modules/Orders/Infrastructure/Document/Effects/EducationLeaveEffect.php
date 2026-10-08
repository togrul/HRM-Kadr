<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

/**
 * Education leave (təhsil məzuniyyəti, ƏM m.112.1(c) and m.123): paid leave for sessions,
 * state exams and the diploma work of an employee who studies while working. It is a
 * leave kind of its own, separate from the annual labour leave (əmək məzuniyyəti), so the
 * employee is put on leave without drawing on the yearly vacation balance.
 */
class EducationLeaveEffect extends VacationEffect
{
    protected function countsAgainstAnnualBalance(): bool
    {
        return false;
    }
}
