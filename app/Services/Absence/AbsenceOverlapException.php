<?php

namespace App\Services\Absence;

use App\Data\AbsencePeriod;
use DomainException;

/**
 * Thrown when a leave, vacation or business trip would put an employee in two places at once.
 * The message is the user-facing Azerbaijani (localised) sentence naming the clash.
 */
class AbsenceOverlapException extends DomainException
{
    public function __construct(string $message, public readonly AbsencePeriod $conflict)
    {
        parent::__construct($message);
    }
}
