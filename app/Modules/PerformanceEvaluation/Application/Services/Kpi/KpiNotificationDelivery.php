<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceNotificationSetting;
use App\Models\PerformanceNotificationTemplate;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Support\KpiMail;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;

/**
 * Delivers every KPI notification (spec §10):
 *  - in-app (bell) always — the `notifications` table is the notification log;
 *  - e-mail through the queue when the user keeps e-mail on; deadline events are
 *    mandatory and are e-mailed even when the user switched e-mail off;
 *  - users on the daily digest get the optional events in one mail a day instead;
 *  - wording comes from HR's template for the event and language when there is one,
 *    with {employee_name}, {deadline}, {link}… filled in; otherwise from the defaults.
 */
class KpiNotificationDelivery
{
    /** Every event HR can reword, in the order the template screen lists them. */
    public const EVENTS = [
        'cycle_opened', 'agreement_requested', 'accepted', 'rejected', 'self_review_started', 'self_review_submitted',
        'calibration_ready', 'approved', 'returned', 'reminder', 'escalation', 'actuals_missing', 'checkin_due',
        'red_zone', 'change_requested', 'change_approved', 'change_rejected', 'manager_assigned', 'manager_released',
        'connector_failed', 'fund_exceeded',
    ];

    /** Deadline events cannot be switched off (spec §10). */
    public const MANDATORY = ['agreement_requested', 'self_review_started', 'self_review_submitted', 'reminder', 'escalation'];

    /** Template placeholder → the value key the notifier passes. */
    public const PLACEHOLDERS = [
        'employee_name' => 'employee', 'cycle' => 'cycle', 'deadline' => 'due', 'status' => 'status', 'kpi' => 'kpi',
        'reason' => 'reason', 'count' => 'count', 'from' => 'from', 'to' => 'to', 'forecast' => 'forecast',
        'threshold' => 'threshold', 'total' => 'total', 'fund' => 'fund', 'currency' => 'currency', 'error' => 'error', 'link' => 'link',
    ];

    /**
     * @param  array<int, int>  $userIds
     * @param  array<string, string>  $replace  values for the event's wording
     * @param  array<string, mixed>  $payload  extra data stored with the in-app notification
     */
    public function deliver(array $userIds, string $key, array $replace, array $payload = []): int
    {
        $users = User::query()->whereIn('id', array_unique(array_filter($userIds)))->get();
        if ($users->isEmpty()) {
            return 0;
        }

        $replace['link'] ??= route('performance-evaluation', ['tab' => str_starts_with($key, 'fund') ? 'kpi_bonus' : ($key === 'connector_failed' ? 'kpi_library' : 'kpi_scorecards')]);
        [$subject, $body] = $this->wording($key, $replace);
        $settings = PerformanceNotificationSetting::query()->whereIn('user_id', $users->modelKeys())->get()->keyBy('user_id');
        $mandatory = in_array($key, self::MANDATORY, true);

        foreach ($users as $user) {
            $user->notify(new PlatformNotification('database', [
                'action' => 'performanceScorecard',
                'module' => 'kpi',
                'event' => $key,
                'category' => __('performance_evaluation::kpi.notifications.category'),
                'message' => $subject,
                'name' => (string) ($replace['employee'] ?? $replace['kpi'] ?? $replace['cycle'] ?? ''),
                'body' => $body,
                ...$payload,
            ], $subject, $body));

            $setting = $settings->get($user->id);
            $wantsMail = $setting->email ?? true;
            $digest = (bool) ($setting->digest ?? false);

            if (filled($user->email) && ($mandatory || ($wantsMail && ! $digest))) {
                $user->notify(new KpiMail($subject, [$body], $replace['link']));
            }
        }

        return $users->count();
    }

    /**
     * One mail per digest user with the optional KPI events of the last day.
     */
    public function sendDigests(): int
    {
        $sent = 0;

        PerformanceNotificationSetting::query()
            ->where('digest', true)
            ->where('email', true)
            ->get()
            ->each(function (PerformanceNotificationSetting $setting) use (&$sent): void {
                $user = User::query()->find($setting->user_id);
                if ($user === null || blank($user->email)) {
                    return;
                }

                $lines = $user->notifications()
                    ->where('created_at', '>=', now()->subDay())
                    ->get()
                    ->filter(fn ($notification): bool => ($notification->data['module'] ?? null) === 'kpi' && ! in_array($notification->data['event'] ?? '', self::MANDATORY, true))
                    ->map(fn ($notification): string => $notification->getAttribute('data')['message'].' — '.$notification->getAttribute('data')['body'])
                    ->values();

                if ($lines->isNotEmpty()) {
                    $user->notify(new KpiMail(__('performance_evaluation::kpi.mail.digest_subject', ['count' => $lines->count()]), $lines->all(), route('performance-evaluation', ['tab' => 'kpi_scorecards'])));
                    $sent++;
                }
            });

        return $sent;
    }

    /**
     * Subject and body in the current language: HR's template, else the default text.
     *
     * @param  array<string, string>  $replace
     * @return array{0: string, 1: string}
     */
    public function wording(string $key, array $replace): array
    {
        $template = PerformanceNotificationTemplate::query()->where('key', $key)->where('locale', app()->getLocale())->first();

        if ($template === null) {
            return [
                __('performance_evaluation::kpi.notifications.'.$key.'.subject', $replace),
                __('performance_evaluation::kpi.notifications.'.$key.'.body', $replace),
            ];
        }

        $values = collect(self::PLACEHOLDERS)->mapWithKeys(fn (string $source, string $name): array => ['{'.$name.'}' => (string) ($replace[$source] ?? '')])->all();

        return [strtr($template->subject, $values), strtr($template->body, $values)];
    }

    /**
     * The default wording of an event in a language, written with template placeholders
     * so HR starts editing from the text people get today.
     *
     * @return array{subject: string, body: string}
     */
    public function defaultTemplate(string $key, string $locale): array
    {
        $map = collect(self::PLACEHOLDERS)->mapWithKeys(fn (string $source, string $name): array => [':'.$source => '{'.$name.'}'])
            ->sortKeysUsing(fn (string $a, string $b): int => strlen($b) <=> strlen($a))
            ->all();

        return [
            'subject' => strtr((string) __('performance_evaluation::kpi.notifications.'.$key.'.subject', [], $locale), $map),
            'body' => strtr((string) __('performance_evaluation::kpi.notifications.'.$key.'.body', [], $locale), $map),
        ];
    }

    /**
     * @return Collection<string, PerformanceNotificationTemplate>
     */
    public function templates(string $locale): Collection
    {
        return PerformanceNotificationTemplate::query()->where('locale', $locale)->get()->keyBy('key');
    }
}
