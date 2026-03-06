<?php

namespace App\Modules\Attendance\Application\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AttendanceAuditLogger
{
    /**
     * @param  array<string,mixed>  $properties
     */
    public function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $causerId = null
    ): void {
        if (! function_exists('activity')) {
            return;
        }

        $logger = activity('attendance')
            ->event($event)
            ->withProperties($properties);

        if ($causerId !== null) {
            $logger->causedBy($causerId);
        } elseif (Auth::check()) {
            $logger->causedBy(Auth::user());
        }

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}

