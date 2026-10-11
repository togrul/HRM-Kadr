<?php

namespace App\Modules\Reports\Application\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hesabatlar modulunun audit izi (spatie/activitylog, log adı `reports`): hesabatın
 * açılması, ixrac, preset və planlı hesabat əməliyyatları. Audit yazısının uğursuzluğu
 * hesabatı dayandırmır — yalnız loglanır.
 */
class ReportsAuditLogger
{
    public const LOG_NAME = 'reports';

    public const EVENT_OPENED = 'report_opened';

    public const EVENT_EXPORTED = 'report_exported';

    public const EVENT_PRESET_CREATED = 'report_preset_created';

    public const EVENT_PRESET_UPDATED = 'report_preset_updated';

    public const EVENT_PRESET_DELETED = 'report_preset_deleted';

    public const EVENT_PRESET_APPLIED = 'report_preset_applied';

    public const EVENT_SCHEDULE_CREATED = 'report_schedule_created';

    public const EVENT_SCHEDULE_UPDATED = 'report_schedule_updated';

    public const EVENT_SCHEDULE_DELETED = 'report_schedule_deleted';

    public const EVENT_SCHEDULE_SENT = 'report_schedule_sent';

    public const EVENT_SCHEDULE_FAILED = 'report_schedule_failed';

    /**
     * @param  array<string,mixed>  $properties
     */
    public function log(
        string $event,
        string $description,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null,
    ): void {
        if (! function_exists('activity')) {
            return;
        }

        try {
            $logger = activity(self::LOG_NAME)
                ->event($event)
                ->withProperties(array_filter(
                    $properties + ['ip' => request()->ip()],
                    fn ($value): bool => $value !== null && $value !== ''
                ));

            if ($causer !== null) {
                $logger->causedBy($causer);
            } elseif (Auth::check()) {
                $logger->causedBy(Auth::user());
            }

            if ($subject !== null) {
                $logger->performedOn($subject);
            }

            $logger->log($description);
        } catch (Throwable $exception) {
            Log::warning('reports.audit.failed', ['event' => $event, 'error' => $exception->getMessage()]);
        }
    }

    /**
     * @param  array<string,mixed>  $context  hesabat seçimi və filtrlər
     */
    public function opened(array $context): void
    {
        $this->log(self::EVENT_OPENED, 'Report opened', $context);
    }

    /**
     * @param  array<string,mixed>  $context  hesabat seçimi və filtrlər
     */
    public function exported(string $format, array $context): void
    {
        $this->log(self::EVENT_EXPORTED, 'Report exported', ['format' => $format] + $context);
    }
}
