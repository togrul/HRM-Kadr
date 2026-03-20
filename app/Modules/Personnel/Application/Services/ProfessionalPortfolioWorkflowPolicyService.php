<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProfessionalPortfolioWorkflowPolicyService
{
    public function assertEventTransition(PersonnelEventRecord $record, string $target): void
    {
        $this->assertTransition($record->verification_status, $target, [
            PersonnelEventRecord::STATUS_PENDING => [
                PersonnelEventRecord::STATUS_VERIFIED,
                PersonnelEventRecord::STATUS_REJECTED,
            ],
            PersonnelEventRecord::STATUS_REJECTED => [
                PersonnelEventRecord::STATUS_VERIFIED,
            ],
        ]);
    }

    public function assertMediaTransition(PersonnelMediaMention $record, string $target): void
    {
        $record->loadMissing('archiveAttachment');

        $this->assertTransition($record->verification_status, $target, [
            PersonnelMediaMention::STATUS_PENDING => [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_REJECTED,
                PersonnelMediaMention::STATUS_BROKEN_LINK,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            ],
            PersonnelMediaMention::STATUS_VERIFIED => [
                PersonnelMediaMention::STATUS_REJECTED,
                PersonnelMediaMention::STATUS_BROKEN_LINK,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            ],
            PersonnelMediaMention::STATUS_BROKEN_LINK => [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
                PersonnelMediaMention::STATUS_REJECTED,
            ],
            PersonnelMediaMention::STATUS_ARCHIVED_ONLY => [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_BROKEN_LINK,
                PersonnelMediaMention::STATUS_REJECTED,
            ],
            PersonnelMediaMention::STATUS_REJECTED => [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            ],
        ]);

        if ($target === PersonnelMediaMention::STATUS_VERIFIED) {
            $this->assertMediaCanBeVerified($record);
        }

        if ($target === PersonnelMediaMention::STATUS_ARCHIVED_ONLY) {
            $this->assertArchiveExists($record);
        }

        if ($target === PersonnelMediaMention::STATUS_BROKEN_LINK
            && ! filled($record->url)
            && ! (bool) config('personnel.portfolio.policy.allow_manual_broken_without_url', false)) {
            throw new HttpException(422, __('personnel::portfolio.messages.media_url_required_for_broken_status'));
        }
    }

    public function assertProjectTransition(PersonnelProjectRecord $record, string $target): void
    {
        $this->assertTransition($record->verification_status, $target, [
            PersonnelProjectRecord::STATUS_PENDING => [
                PersonnelProjectRecord::STATUS_VERIFIED,
                PersonnelProjectRecord::STATUS_REJECTED,
            ],
            PersonnelProjectRecord::STATUS_REJECTED => [
                PersonnelProjectRecord::STATUS_VERIFIED,
            ],
        ]);
    }

    public function recommendedMediaStatus(PersonnelMediaMention $record): ?string
    {
        $record->loadMissing('archiveAttachment');

        $archiveMissing = ! $record->archiveAttachment || ($record->archive_health_status === 'missing');
        $linkBroken = $record->link_check_status === 'broken';

        if ($archiveMissing
            && in_array($record->verification_status, [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            ], true)
            && (bool) config('personnel.portfolio.policy.reject_media_without_archive', true)) {
            return PersonnelMediaMention::STATUS_REJECTED;
        }

        if ($linkBroken && ! $archiveMissing && (bool) config('personnel.portfolio.policy.auto_archive_on_broken_link', true)) {
            return PersonnelMediaMention::STATUS_ARCHIVED_ONLY;
        }

        if ($linkBroken && $archiveMissing && (bool) config('personnel.portfolio.policy.reject_broken_media_without_archive', true)) {
            return PersonnelMediaMention::STATUS_REJECTED;
        }

        return null;
    }

    private function assertTransition(string $current, string $target, array $map): void
    {
        if (! in_array($target, $map[$current] ?? [], true)) {
            throw new HttpException(422, __('personnel::portfolio.messages.invalid_transition'));
        }
    }

    private function assertMediaCanBeVerified(PersonnelMediaMention $record): void
    {
        $this->assertArchiveExists($record);

        if ((bool) config('personnel.portfolio.policy.block_verification_when_link_broken', true)
            && $record->link_check_status === 'broken') {
            throw new HttpException(422, __('personnel::portfolio.messages.media_link_must_be_healthy'));
        }
    }

    private function assertArchiveExists(PersonnelMediaMention $record): void
    {
        if ((bool) config('personnel.portfolio.policy.require_archive_for_media_verification', true)
            && ! $record->archiveAttachment) {
            throw new HttpException(422, __('personnel::portfolio.messages.media_archive_required'));
        }

        if ((bool) config('personnel.portfolio.policy.require_healthy_archive_for_media_verification', true)
            && $record->archive_health_status === 'missing') {
            throw new HttpException(422, __('personnel::portfolio.messages.media_archive_must_exist'));
        }
    }
}
