<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\PersonnelMediaMention;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProfessionalPortfolioLinkHealthService
{
    public function check(PersonnelMediaMention $record): array
    {
        $record->loadMissing('archiveAttachment');

        [$linkStatus, $linkMessage, $httpCode] = $this->checkUrl($record->url);
        [$archiveStatus, $archiveMessage] = $this->checkArchive($record);

        $record->forceFill([
            'link_check_status' => $linkStatus,
            'link_check_message' => $linkMessage,
            'link_check_http_code' => $httpCode,
            'link_checked_at' => now(),
            'archive_health_status' => $archiveStatus,
            'archive_health_message' => $archiveMessage,
            'archive_checked_at' => now(),
        ]);

        $recommendedStatus = app(ProfessionalPortfolioWorkflowPolicyService::class)->recommendedMediaStatus($record);
        if ($recommendedStatus !== null) {
            $record->verification_status = $recommendedStatus;
        } elseif ($linkStatus === 'broken' && $record->verification_status === PersonnelMediaMention::STATUS_VERIFIED) {
            $record->verification_status = PersonnelMediaMention::STATUS_BROKEN_LINK;
        }

        $record->save();

        return [
            'id' => $record->id,
            'link_status' => $linkStatus,
            'archive_status' => $archiveStatus,
            'verification_status' => $record->verification_status,
        ];
    }

    private function checkUrl(?string $url): array
    {
        if (! filled($url)) {
            return ['skipped', __('personnel::portfolio.messages.link_check_skipped'), null];
        }

        try {
            $response = Http::timeout((int) config('personnel.portfolio.link_health.timeout_seconds', 8))
                ->withoutVerifying()
                ->head($url);

            if ($this->isSuccessfulResponse($response->status())) {
                return ['ok', __('personnel::portfolio.messages.link_check_ok'), $response->status()];
            }

            $fallback = Http::timeout((int) config('personnel.portfolio.link_health.timeout_seconds', 8))
                ->withoutVerifying()
                ->get($url);

            if ($this->isSuccessfulResponse($fallback->status())) {
                return ['ok', __('personnel::portfolio.messages.link_check_ok'), $fallback->status()];
            }

            return ['broken', __('personnel::portfolio.messages.link_check_failed'), $fallback->status()];
        } catch (Throwable $e) {
            return ['broken', $e->getMessage(), null];
        }
    }

    private function checkArchive(PersonnelMediaMention $record): array
    {
        $attachment = $record->archiveAttachment;
        if (! $attachment || ! filled($attachment->file_path)) {
            return ['missing', __('personnel::portfolio.messages.archive_health_missing')];
        }

        $exists = Storage::disk($attachment->disk ?: 'public')->exists($attachment->file_path);

        return $exists
            ? ['ok', __('personnel::portfolio.messages.archive_health_ok')]
            : ['missing', __('personnel::portfolio.messages.archive_health_missing')];
    }

    private function isSuccessfulResponse(int $status): bool
    {
        return ($status >= 200 && $status < 400) || $status === 405;
    }
}
