<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\User;
use App\Modules\Reports\Application\Mail\ScheduledReportMail;
use App\Modules\Reports\Domain\Models\ReportSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Planlı hesabatın bir icrası. Hesabat SAHİBİN adından qurulur: icazələri (hesabat qrupu,
 * ixrac, planlaşdırma) icra anında yenidən yoxlanılır və struktur görünürlüyü sahibinki olur —
 * faylda sahibin görmədiyi heç nə olmur. Sahibin icazəsi və ya seçilmiş strukturu artıq
 * yoxdursa göndəriş dayanır. Uğursuzluq həm xəta jurnalına, həm audit jurnalına yazılır və
 * planın `last_status`/`last_error` sahələrində görünür; növbəti icra vaxtı hər halda irəliləyir.
 */
class ScheduledReportRunner
{
    public function __construct(
        private ReportsAccessService $access,
        private ReportSelectionService $selections,
        private ReportScheduleService $schedules,
        private ReportFileBuilder $files,
        private ReportsAuditLogger $audit,
    ) {}

    public function run(ReportSchedule $schedule, ?CarbonInterface $now = null): string
    {
        $now = CarbonImmutable::instance($now ?? now());
        $owner = $schedule->owner;
        $recipientIds = [];

        try {
            if (! $owner instanceof User || ! $owner->is_active) {
                throw new RuntimeException('owner_inactive');
            }

            if (! $this->access->canSchedule($owner)) {
                throw new RuntimeException('owner_not_allowed');
            }

            $recipients = $this->schedules->eligibleRecipients(
                $schedule->tab,
                $schedule->filters,
                $schedule->recipients()->pluck('users.id')->map(fn ($id): int => (int) $id)->all(),
                $owner,
            );
            $recipientIds = $recipients->pluck('id')->all();

            if ($recipients->isEmpty()) {
                throw new RuntimeException('no_recipients');
            }

            $file = $this->actingAs($owner, fn (): array => $this->buildFile($schedule, $owner, $now));

            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send(new ScheduledReportMail(
                    scheduleName: $schedule->name,
                    reportTitle: $file['title'],
                    periodLabel: $file['period'],
                    ownerName: (string) $owner->name,
                    attachmentContent: $file['content'],
                    attachmentName: $file['filename'],
                    attachmentMime: $file['mime'],
                ));
            }

            $this->record($schedule, $now, ReportSchedule::STATUS_SENT, null);
            $this->audit->log(ReportsAuditLogger::EVENT_SCHEDULE_SENT, 'Scheduled report sent', [
                'schedule_id' => $schedule->id,
                'schedule_name' => $schedule->name,
                'format' => $schedule->format,
                'recipient_ids' => $recipientIds,
                'filename' => $file['filename'],
            ], $schedule, $owner);
            Log::info('reports.schedule.sent', ['schedule_id' => $schedule->id, 'recipients' => count($recipientIds)]);

            return ReportSchedule::STATUS_SENT;
        } catch (Throwable $exception) {
            $reason = $exception instanceof ValidationException
                ? (string) collect($exception->errors())->flatten()->first()
                : $exception->getMessage();

            $this->record($schedule, $now, ReportSchedule::STATUS_FAILED, $reason);
            $this->audit->log(ReportsAuditLogger::EVENT_SCHEDULE_FAILED, 'Scheduled report failed', [
                'schedule_id' => $schedule->id,
                'schedule_name' => $schedule->name,
                'reason' => Str::limit($reason, 500),
                'recipient_ids' => $recipientIds,
            ], $schedule, $owner instanceof User ? $owner : null);
            Log::error('reports.schedule.failed', [
                'schedule_id' => $schedule->id,
                'owner_id' => $schedule->user_id,
                'reason' => $reason,
                'exception' => $exception::class,
            ]);

            return ReportSchedule::STATUS_FAILED;
        }
    }

    /**
     * @return array{content:string,filename:string,mime:string,title:string,period:string}
     */
    private function buildFile(ReportSchedule $schedule, User $owner, CarbonImmutable $now): array
    {
        $period = $schedule->reportPeriod($now);
        $resolved = $this->selections->forViewer($owner, $schedule->tab, $schedule->filters + $period);

        if ($resolved['structure_dropped']) {
            throw new RuntimeException('structure_out_of_scope');
        }

        $filters = $resolved['filters'];
        $payload = $this->files->payload($schedule->tab, $filters, [
            'year' => $period['year'],
            'month' => $period['month'],
            'structure_id' => $filters['structure_id'] ?? null,
        ]);

        $periodLabel = CarbonImmutable::create($period['year'], $period['month'], 1)->translatedFormat('F Y');

        $file = $this->files->file(
            $payload,
            $schedule->format,
            'report-'.Str::slug($schedule->name).'-'.sprintf('%04d-%02d', $period['year'], $period['month']),
            $this->files->meta($period + ['structure_id' => $filters['structure_id'] ?? null], (string) $owner->name),
        );

        return $file + ['title' => (string) ($payload['title'] ?? $schedule->name), 'period' => $periodLabel];
    }

    /**
     * Hesabat servisləri cari istifadəçinin struktur görünürlüyünü oxuyur: icra müddətində
     * cari istifadəçi sahib olur, sonra əvvəlki vəziyyət bərpa olunur.
     *
     * @template T
     *
     * @param  Closure():T  $callback
     * @return T
     */
    private function actingAs(User $owner, Closure $callback): mixed
    {
        $guard = Auth::guard();
        $previous = $guard->user();
        $guard->setUser($owner);

        try {
            return $callback();
        } finally {
            if ($previous !== null) {
                $guard->setUser($previous);
            } else {
                $guard->forgetUser();
            }
        }
    }

    private function record(ReportSchedule $schedule, CarbonImmutable $now, string $status, ?string $error): void
    {
        $schedule->forceFill([
            'last_run_at' => $now,
            'last_status' => $status,
            'last_error' => $error !== null ? Str::limit($error, 1000) : null,
            'next_run_at' => $schedule->is_active ? $schedule->nextRunAfter($now) : null,
        ])->save();
    }
}
