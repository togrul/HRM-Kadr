<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\PersonnelMediaMention;
use App\Support\Http\SafeUrl;
use App\Support\Http\UnsafeUrlException;
use App\Support\Uploads\PrivateFiles;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProfessionalPortfolioLinkHealthService
{
    public function __construct(private readonly SafeUrl $safeUrl) {}

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

    /**
     * İstifadəçinin daxil etdiyi linki yoxlayır. SSRF-ə qarşı: URL {@see SafeUrl} ilə
     * yoxlanır (daxili ünvanlar bloklanır), sorğu yoxlanmış IP-yə bağlanır, yönləndirmə
     * izlənmir (3xx "ok" sayılır), TLS sertifikatı yoxlanır. İstisna mətni saxlanmır.
     */
    private function checkUrl(?string $url): array
    {
        if (! filled($url)) {
            return ['skipped', __('personnel::portfolio.messages.link_check_skipped'), null];
        }

        try {
            $target = $this->safeUrl->assert((string) $url);
        } catch (UnsafeUrlException $e) {
            Log::info('Portfel link yoxlaması: URL bloklandı.', ['reason' => $e->getMessage()]);

            return ['broken', __('personnel::portfolio.messages.link_check_failed'), null];
        }

        $timeout = (int) config('personnel.portfolio.link_health.timeout_seconds', 8);

        try {
            $response = $this->safeUrl->pin(Http::timeout($timeout), $target)->head($target->url);

            if ($this->isSuccessfulResponse($response->status())) {
                return ['ok', __('personnel::portfolio.messages.link_check_ok'), $response->status()];
            }

            $fallback = $this->safeUrl->pin(Http::timeout($timeout), $target)->get($target->url);

            if ($this->isSuccessfulResponse($fallback->status())) {
                return ['ok', __('personnel::portfolio.messages.link_check_ok'), $fallback->status()];
            }

            return ['broken', __('personnel::portfolio.messages.link_check_failed'), $fallback->status()];
        } catch (Throwable $e) {
            Log::info('Portfel link yoxlaması uğursuz oldu.', ['error' => $e->getMessage()]);

            return ['broken', __('personnel::portfolio.messages.link_check_failed'), null];
        }
    }

    private function checkArchive(PersonnelMediaMention $record): array
    {
        $attachment = $record->archiveAttachment;
        if (! $attachment || ! filled($attachment->file_path)) {
            return ['missing', __('personnel::portfolio.messages.archive_health_missing')];
        }

        $exists = PrivateFiles::locate($attachment->file_path, $attachment->disk) !== null;

        return $exists
            ? ['ok', __('personnel::portfolio.messages.archive_health_ok')]
            : ['missing', __('personnel::portfolio.messages.archive_health_missing')];
    }

    private function isSuccessfulResponse(int $status): bool
    {
        return ($status >= 200 && $status < 400) || $status === 405;
    }
}
