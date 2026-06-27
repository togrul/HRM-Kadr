<?php

namespace App\Modules\Notifications\Support;

use App\Mail\NotificationCampaignMail;
use App\Models\AttendanceCalendar;
use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\Personnel;
use App\Notifications\BirthdayNotification;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NotificationCampaignDispatcher
{
    protected function duplicateTitlePattern(): string
    {
        $copySuffix = trim((string) __('notifications::common.badges.copy_suffix'));
        $copyLabel = trim((string) __('notifications::common.badges.copy_label'));

        $parts = array_filter([
            preg_quote($copySuffix, '/'),
            '\('.preg_quote($copyLabel, '/').'\)',
            '\(surət\)',
            '\(copy\)',
        ]);

        return '/(?:\s*(?:'.implode('|', $parts).'))+$/iu';
    }

    protected function baseDuplicateTitle(string $title): string
    {
        return trim((string) preg_replace($this->duplicateTitlePattern(), '', $title));
    }

    public function __construct(
        protected NotificationAudienceResolver $audienceResolver,
        protected NotificationTemplateRenderer $templateRenderer,
        protected NotificationCountCache $countCache,
        protected DispatchRetryScheduler $retryScheduler,
        protected NotificationPayloadFactory $payloads,
    ) {}

    public function dispatchBirthday(Personnel $personnel): int
    {
        $trigger = NotificationTriggerRegistry::trigger('birthday') ?? 'birthday_due';

        $rules = NotificationRule::query()
            ->with('template:id,key,subject_template,body_template,channel,format')
            ->where('category', 'birthday')
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        $sentDispatches = 0;

        foreach ($rules as $rule) {
            $recipients = $this->audienceResolver->resolve((array) $rule->audience_config, $personnel);

            if ($recipients->isEmpty()) {
                continue;
            }

            $campaign = NotificationCampaign::query()->create([
                'category' => 'birthday',
                'trigger' => $trigger,
                'template_id' => $rule->template_id,
                'title' => 'Ad günü bildirişi: '.$personnel->fullname,
                'channel' => $rule->channel,
                'audience_config' => $rule->audience_config,
                'payload' => $this->payloads->birthday($personnel),
                'format' => $rule->template?->format ?? 'text',
                'status' => $rule->approval_required ? 'draft' : 'queued',
                'approval_status' => $rule->approval_required ? 'pending' : 'not_required',
                'created_by' => auth()->id(),
            ]);

            if ($rule->approval_required) {
                continue;
            }

            $sentDispatches += $this->dispatchCampaignToRecipients($campaign, $rule, $recipients, $personnel);
        }

        return $sentDispatches;
    }

    public function dispatchPositionChange(Personnel $personnel, array $changes): int
    {
        $trigger = NotificationTriggerRegistry::trigger('position_change') ?? 'position_changed';

        $rules = NotificationRule::query()
            ->with('template:id,key,subject_template,body_template,channel,format')
            ->where('category', 'position_change')
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        $sentDispatches = 0;

        foreach ($rules as $rule) {
            $payload = $this->payloads->positionChange($personnel, $changes);
            $recipients = $this->audienceResolver->resolve((array) $rule->audience_config, $personnel);

            if ($recipients->isEmpty()) {
                continue;
            }

            $campaign = NotificationCampaign::query()->create([
                'category' => 'position_change',
                'trigger' => $trigger,
                'template_id' => $rule->template_id,
                'title' => 'Vəzifə dəyişikliyi: '.$personnel->fullname,
                'channel' => $rule->channel,
                'audience_config' => $rule->audience_config,
                'payload' => $payload,
                'format' => $rule->template?->format ?? 'text',
                'status' => $rule->approval_required ? 'draft' : 'queued',
                'approval_status' => $rule->approval_required ? 'pending' : 'not_required',
                'created_by' => auth()->id(),
            ]);

            if ($rule->approval_required) {
                continue;
            }

            $sentDispatches += $this->dispatchCampaign($campaign, $personnel);
        }

        return $sentDispatches;
    }

    public function dispatchEmploymentStarted(Personnel $personnel, array $context = []): int
    {
        $trigger = NotificationTriggerRegistry::trigger('employment_started') ?? 'employment_started';

        $rules = NotificationRule::query()
            ->with('template:id,key,subject_template,body_template,channel,format')
            ->where('category', 'employment_started')
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        $payload = $this->payloads->employmentStarted($personnel, $context);
        $sentDispatches = 0;

        if ($rules->isEmpty()) {
            $recipients = $this->audienceResolver->resolve([
                'targets' => ['manager_chain'],
            ], $personnel);

            if ($recipients->isEmpty()) {
                return 0;
            }

            $campaign = NotificationCampaign::query()->create([
                'category' => 'employment_started',
                'trigger' => $trigger,
                'template_id' => null,
                'title' => 'İşə başlayan əməkdaş: '.$personnel->fullname,
                'channel' => 'database',
                'audience_config' => [
                    'targets' => ['specific_users'],
                    'user_ids' => $recipients->pluck('id')->all(),
                ],
                'payload' => $payload,
                'format' => 'text',
                'status' => 'queued',
                'approval_status' => 'not_required',
                'created_by' => auth()->id(),
            ]);

            return $this->dispatchCampaign($campaign, $personnel);
        }

        foreach ($rules as $rule) {
            $recipients = $this->audienceResolver->resolve((array) $rule->audience_config, $personnel);

            if ($recipients->isEmpty()) {
                continue;
            }

            $campaign = NotificationCampaign::query()->create([
                'category' => 'employment_started',
                'trigger' => $trigger,
                'template_id' => $rule->template_id,
                'title' => 'İşə başlayan əməkdaş: '.$personnel->fullname,
                'channel' => $rule->channel,
                'audience_config' => $rule->audience_config,
                'payload' => $payload,
                'format' => $rule->template?->format ?? 'text',
                'status' => $rule->approval_required ? 'draft' : 'queued',
                'approval_status' => $rule->approval_required ? 'pending' : 'not_required',
                'created_by' => auth()->id(),
            ]);

            if ($rule->approval_required) {
                continue;
            }

            $sentDispatches += $this->dispatchCampaign($campaign, $personnel);
        }

        return $sentDispatches;
    }

    public function dispatchHoliday(AttendanceCalendar $calendar): int
    {
        $trigger = NotificationTriggerRegistry::trigger('holiday') ?? 'holiday_due';

        $rules = NotificationRule::query()
            ->with('template:id,key,subject_template,body_template,channel,format')
            ->where('category', 'holiday')
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        $sentDispatches = 0;

        foreach ($rules as $rule) {
            $payload = $this->payloads->holiday($calendar);
            $context = ['structure_id' => data_get($payload, 'structure_id')];
            $recipients = $this->audienceResolver->resolve((array) $rule->audience_config, null, $context);

            if ($recipients->isEmpty()) {
                continue;
            }

            $campaign = NotificationCampaign::query()->create([
                'category' => 'holiday',
                'trigger' => $trigger,
                'template_id' => $rule->template_id,
                'title' => 'Bayram / tətil bildirişi: '.($calendar->name ?: $calendar->date?->format('d.m.Y')),
                'channel' => $rule->channel,
                'audience_config' => $rule->audience_config,
                'payload' => $payload,
                'format' => $rule->template?->format ?? 'text',
                'status' => $rule->approval_required ? 'draft' : 'queued',
                'approval_status' => $rule->approval_required ? 'pending' : 'not_required',
                'created_by' => auth()->id(),
            ]);

            if ($rule->approval_required) {
                continue;
            }

            $sentDispatches += $this->dispatchCampaign($campaign);
        }

        return $sentDispatches;
    }

    public function createAnnouncementCampaign(array $data): NotificationCampaign
    {
        return $this->createManualCampaign($data);
    }

    public function createManualCampaign(array $data): NotificationCampaign
    {
        $structureIds = $this->parseIntegerList($data['structure_ids'] ?? '');
        $userIds = $this->parseIntegerList($data['user_ids'] ?? '');
        $category = (string) ($data['category'] ?? 'announcement');
        $audienceTargets = NotificationAudienceTargetRegistry::normalize(
            $data['audience_targets'] ?? 'all_employees',
            'announcements',
        );
        $scheduledAt = filled($data['scheduled_at'] ?? null)
            ? Carbon::parse((string) $data['scheduled_at'])
            : null;

        $campaign = NotificationCampaign::query()->create([
            'category' => $category,
            'trigger' => $scheduledAt
                ? (NotificationTriggerRegistry::trigger($category, 'scheduled')
                    ?? NotificationTriggerRegistry::trigger($category, 'manual')
                    ?? NotificationTriggerRegistry::firstForCategory($category))
                : (NotificationTriggerRegistry::trigger($category, 'manual')
                    ?? NotificationTriggerRegistry::firstForCategory($category)
                    ?? ($category === 'holiday' ? 'manual_holiday' : 'manual_announcement')),
            'template_id' => $data['template_id'] ?? null,
            'title' => trim((string) ($data['title'] ?? $data['holiday_name'] ?? __('notifications::common.announcements.preview_title_fallback'))),
            'channel' => (string) ($data['channel'] ?? 'database'),
            'audience_config' => [
                'targets' => $audienceTargets !== [] ? $audienceTargets : ['all_employees'],
                'structure_ids' => $structureIds,
                'user_ids' => $userIds,
            ],
            'payload' => $category === 'holiday'
                ? $this->payloads->manualHoliday($data)
                : $this->payloads->manualAnnouncement($data),
            'format' => (string) ($data['format'] ?? 'text'),
            'status' => ! empty($data['approval_required']) ? 'draft' : 'queued',
            'approval_status' => ! empty($data['approval_required']) ? 'pending' : 'not_required',
            'scheduled_at' => $scheduledAt,
            'created_by' => auth()->id(),
        ]);

        $this->logCampaignAction($campaign, 'created');

        if (empty($data['approval_required']) && ! empty($data['send_now'])) {
            $this->dispatchCampaign($campaign);
        }

        return $campaign;
    }

    protected function parseIntegerList(string|array|null $value): array
    {
        return collect(is_array($value) ? $value : explode(',', (string) $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $item) => $item > 0)
            ->values()
            ->all();
    }

    public function approveCampaign(NotificationCampaign $campaign, ?string $note = null): int
    {
        $this->logCampaignAction($campaign, 'approved', $note);

        $campaign->update([
            'approval_status' => 'approved',
            'status' => 'queued',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->dispatchCampaign($campaign);
    }

    public function rejectCampaign(NotificationCampaign $campaign, ?string $note = null): void
    {
        $this->logCampaignAction($campaign, 'rejected', $note);

        $campaign->update([
            'approval_status' => 'rejected',
            'status' => 'cancelled',
        ]);
    }

    public function duplicateCampaign(NotificationCampaign $campaign, bool $dispatchNow = false): NotificationCampaign
    {
        $campaign->loadMissing('template:id');

        $copy = NotificationCampaign::query()->create([
            'category' => $campaign->category,
            'trigger' => $campaign->trigger,
            'template_id' => $campaign->template_id,
            'title' => $this->baseDuplicateTitle($campaign->title),
            'channel' => $campaign->channel,
            'audience_config' => $campaign->audience_config,
            'payload' => $campaign->payload,
            'format' => $campaign->format,
            'status' => 'draft',
            'approval_status' => $campaign->approval_status === 'approved' ? 'approved' : 'not_required',
            'scheduled_at' => null,
            'approved_at' => $campaign->approval_status === 'approved' ? now() : null,
            'approved_by' => $campaign->approval_status === 'approved' ? auth()->id() : null,
            'created_by' => auth()->id(),
        ]);

        $this->logCampaignAction($copy, 'duplicated', __('notifications::common.helpers.duplicated_from', ['title' => $this->baseDuplicateTitle($campaign->title)]));

        if ($dispatchNow) {
            $copy->update([
                'status' => 'queued',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            $this->dispatchCampaign($copy, forceNow: true);
        }

        return $copy;
    }

    public function retryFailedDispatches(NotificationCampaign $campaign, ?Personnel $subject = null): int
    {
        $campaign->loadMissing('template:id,key,subject_template,body_template,channel,format');
        $subject ??= $this->resolveSubjectFromCampaign($campaign);

        $failedDispatches = $campaign->dispatches()
            ->with('user:id,name,email,is_active')
            ->where('status', 'failed')
            ->get();

        $count = 0;

        foreach ($failedDispatches as $dispatch) {
            if (! $this->retryScheduler->isReadyForRetry($dispatch)) {
                continue;
            }

            $recipient = $dispatch->user;

            if (! $recipient) {
                continue;
            }

            try {
                $deliveryMeta = $this->deliverToRecipient($campaign, $recipient, $subject);

                $dispatch->update([
                    'status' => 'sent',
                    'attempt_count' => ((int) $dispatch->attempt_count) + 1,
                    'last_attempt_at' => now(),
                    'provider_message_id' => data_get($deliveryMeta, 'provider_message_id'),
                    'meta' => $this->retryScheduler->markDispatchMetaAsSent((array) data_get($deliveryMeta, 'meta', [])),
                    'error_message' => null,
                    'failed_at' => null,
                    'sent_at' => now(),
                ]);

                $this->countCache->forgetUser((int) $recipient->id);
                $count++;
            } catch (Throwable $e) {
                $dispatch->update([
                    'status' => 'failed',
                    'attempt_count' => ((int) $dispatch->attempt_count) + 1,
                    'last_attempt_at' => now(),
                    'error_message' => $e->getMessage(),
                    'meta' => $this->retryScheduler->failedDispatchMeta(
                        recipientEmail: $recipient->email,
                        channel: $campaign->channel,
                        attemptCount: ((int) $dispatch->attempt_count) + 1,
                        existingMeta: (array) ($dispatch->meta ?? []),
                        driver: $campaign->channel === 'mail' ? (string) config('mail.default') : null,
                    ),
                    'failed_at' => now(),
                ]);
            }
        }

        $campaign->update([
            'status' => $campaign->dispatches()->where('status', 'failed')->exists() ? 'failed' : 'sent',
        ]);

        $this->logCampaignAction($campaign, 'retried', __('notifications::common.helpers.retried_dispatches', ['count' => $count]));

        return $count;
    }

    public function dispatchCampaign(NotificationCampaign $campaign, ?Personnel $subject = null, bool $forceNow = false): int
    {
        $campaign->loadMissing('template:id,key,subject_template,body_template,channel,format');

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture() && ! $forceNow) {
            $campaign->update(['status' => 'queued']);

            return 0;
        }

        $subject ??= $this->resolveSubjectFromCampaign($campaign);
        $recipients = $this->audienceResolver->resolve(
            (array) ($campaign->audience_config ?? []),
            $subject,
            ['structure_id' => data_get($campaign->payload, 'structure_id')]
        );

        if ($recipients->isEmpty()) {
            $campaign->update(['status' => 'failed']);
            $this->logCampaignAction($campaign, 'failed', __('notifications::common.helpers.no_recipients'));

            return 0;
        }

        $count = 0;

        foreach ($recipients as $recipient) {
            $dispatch = NotificationDispatch::query()->create([
                'campaign_id' => $campaign->id,
                'user_id' => $recipient->id,
                'channel' => $campaign->channel ?: 'database',
                'status' => 'pending',
                'attempt_count' => 0,
            ]);

            try {
                $deliveryMeta = $this->deliverToRecipient($campaign, $recipient, $subject);

                $dispatch->update([
                    'status' => 'sent',
                    'attempt_count' => 1,
                    'last_attempt_at' => now(),
                    'provider_message_id' => data_get($deliveryMeta, 'provider_message_id'),
                    'meta' => $this->retryScheduler->markDispatchMetaAsSent((array) data_get($deliveryMeta, 'meta', [])),
                    'sent_at' => now(),
                ]);

                $this->countCache->forgetUser((int) $recipient->id);
                $count++;
            } catch (Throwable $e) {
                $dispatch->update([
                    'status' => 'failed',
                    'attempt_count' => 1,
                    'last_attempt_at' => now(),
                    'failed_at' => now(),
                    'error_message' => $e->getMessage(),
                    'meta' => $this->retryScheduler->failedDispatchMeta(
                        recipientEmail: $recipient->email,
                        channel: $campaign->channel,
                        attemptCount: 1,
                        driver: $campaign->channel === 'mail' ? (string) config('mail.default') : null,
                    ),
                ]);
            }
        }

        $campaign->update([
            'status' => $count > 0 ? 'sent' : 'failed',
        ]);

        $this->logCampaignAction($campaign, $count > 0 ? 'sent' : 'failed');

        return $count;
    }

    protected function dispatchCampaignToRecipients(
        NotificationCampaign $campaign,
        NotificationRule $rule,
        Collection $recipients,
        Personnel $personnel
    ): int {
        $count = 0;

        foreach ($recipients as $recipient) {
            $dispatch = NotificationDispatch::query()->create([
                'campaign_id' => $campaign->id,
                'user_id' => $recipient->id,
                'channel' => $rule->channel,
                'status' => 'pending',
                'attempt_count' => 0,
            ]);

            try {
                $deliveryMeta = $this->deliverToRecipient($campaign, $recipient, $personnel, $rule->template, $rule->channel);

                $dispatch->update([
                    'status' => 'sent',
                    'attempt_count' => 1,
                    'last_attempt_at' => now(),
                    'provider_message_id' => data_get($deliveryMeta, 'provider_message_id'),
                    'meta' => $this->retryScheduler->markDispatchMetaAsSent((array) data_get($deliveryMeta, 'meta', [])),
                    'sent_at' => now(),
                ]);

                $this->countCache->forgetUser((int) $recipient->id);
                $count++;
            } catch (Throwable $e) {
                $dispatch->update([
                    'status' => 'failed',
                    'attempt_count' => 1,
                    'last_attempt_at' => now(),
                    'failed_at' => now(),
                    'error_message' => $e->getMessage(),
                    'meta' => $this->retryScheduler->failedDispatchMeta(
                        recipientEmail: $recipient->email,
                        channel: $rule->channel,
                        attemptCount: 1,
                        driver: $rule->channel === 'mail' ? (string) config('mail.default') : null,
                    ),
                ]);
            }
        }

        $campaign->update([
            'status' => $count > 0 ? 'sent' : 'failed',
        ]);

        $this->logCampaignAction($campaign, $count > 0 ? 'sent' : 'failed');

        return $count;
    }

    protected function notificationForCampaign(
        NotificationCampaign $campaign,
        ?Personnel $personnel = null,
        ?NotificationTemplate $template = null,
        ?string $channel = null
    ): BirthdayNotification|PlatformNotification {
        $template ??= $campaign->template;
        $channel ??= $campaign->channel ?: $template?->channel ?: 'database';
        $payload = (array) ($campaign->payload ?? []);

        if ($campaign->category === 'birthday' && $personnel) {
            return new BirthdayNotification(
                personnel: $personnel,
                channel: $channel,
                renderedSubject: $this->renderSubject($template, $payload),
                renderedBody: $this->renderBody($template, $payload),
                payload: $payload,
            );
        }

        return new PlatformNotification(
            channel: $channel,
            payload: $payload,
            subject: $this->renderSubject($template, $payload),
            body: $this->renderBody($template, $payload),
        );
    }

    protected function renderSubject(?NotificationTemplate $template, array $payload): ?string
    {
        if (! $template || blank($template->subject_template)) {
            return null;
        }

        return $this->templateRenderer->render($template->subject_template, $payload);
    }

    protected function renderBody(?NotificationTemplate $template, array $payload): string
    {
        if (! $template) {
            return '';
        }

        return $this->templateRenderer->render($template->body_template, $payload);
    }

    protected function deliverToRecipient(
        NotificationCampaign $campaign,
        $recipient,
        ?Personnel $subject = null,
        ?NotificationTemplate $template = null,
        ?string $channel = null
    ): array {
        $template ??= $campaign->template;
        $channel ??= $campaign->channel ?: $template?->channel ?: 'database';
        $payload = (array) ($campaign->payload ?? []);

        if ($channel === 'mail') {
            if (blank($recipient->email)) {
                throw new RuntimeException('Recipient e-poçt ünvanı yoxdur.');
            }

            Mail::to($recipient->email)->send(new NotificationCampaignMail(
                subjectLine: $this->renderSubject($template, $payload) ?: __('notifications::common.mail.subject_notification'),
                body: $this->renderBody($template, $payload),
                isHtml: ($campaign->format ?: $template?->format ?: 'text') === 'html',
            ));

            return [
                'provider_message_id' => 'mail-'.Str::uuid(),
                'meta' => [
                    'driver' => config('mail.default'),
                    'recipient_email' => $recipient->email,
                    'channel' => 'mail',
                    'delivery_mode' => 'sync',
                ],
            ];
        }

        $recipient->notify($this->notificationForCampaign($campaign, $subject, $template, $channel));

        return [
            'provider_message_id' => null,
            'meta' => [
                'channel' => 'database',
                'recipient_email' => $recipient->email,
                'notification_type' => data_get($payload, 'type'),
            ],
        ];
    }

    protected function resolveSubjectFromCampaign(NotificationCampaign $campaign): ?Personnel
    {
        $personnelId = data_get($campaign->payload, 'personnel_id');

        if (! $personnelId) {
            return null;
        }

        return Personnel::query()
            ->with(['position:id,name', 'structure:id,parent_id,name'])
            ->find($personnelId);
    }

    protected function logCampaignAction(NotificationCampaign $campaign, string $action, ?string $note = null): void
    {
        $campaign->approvals()->create([
            'action' => $action,
            'note' => $note,
            'acted_by' => auth()->id(),
            'acted_at' => now(),
        ]);
    }
}
