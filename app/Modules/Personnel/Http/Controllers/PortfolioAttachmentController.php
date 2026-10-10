<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use App\Models\ProfessionalRecordAttachment;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Peşəkar portfel əlavəsini (sertifikat, gündəlik, arxiv nüsxəsi, sübut) verir.
 *
 * Əlavənin öz sahib sütunu yoxdur — onu göstərən qeyd (tədbir / media / layihə) tapılır və
 * həmin qeyd növünün baxış icazəsi yoxlanır (Livewire panelləri ilə eyni matris). Məhdud
 * (restricted) media yalnız `view-restricted-media-records` ilə açılır. Əməkdaş öz
 * portfelinin əlavələrini görə bilər.
 */
class PortfolioAttachmentController
{
    public function __invoke(Request $request, ProfessionalRecordAttachment $attachment): StreamedResponse
    {
        $user = $request->user();
        [$personnelId, $permissions, $restricted] = $this->owner($attachment);

        abort_if($personnelId === null, 404);

        $allowed = PrivateFiles::isOwnPersonnel($user, $personnelId)
            || ($user !== null && $user->canAny($permissions) && (! $restricted || $user->can('view-restricted-media-records')));

        abort_unless($allowed, 403);

        $disk = PrivateFiles::locate($attachment->file_path, $attachment->disk);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk(
            $disk,
            (string) $attachment->file_path,
            $attachment->getAttribute('original_name') ?: $attachment->getAttribute('display_name'),
            $request->boolean('inline'),
        );
    }

    /**
     * @return array{0: ?int, 1: list<string>, 2: bool}
     */
    private function owner(ProfessionalRecordAttachment $attachment): array
    {
        $id = (int) $attachment->getKey();

        $event = PersonnelEventRecord::query()
            ->where(fn ($query) => $query->where('certificate_attachment_id', $id)->orWhere('agenda_attachment_id', $id))
            ->first(['id', 'personnel_id']);
        if ($event) {
            return [(int) $event->getAttribute('personnel_id'), ProfessionalPortfolioPermissionMatrix::eventViewPermissions(), false];
        }

        $media = PersonnelMediaMention::query()
            ->where(fn ($query) => $query->where('archive_attachment_id', $id)->orWhere('screenshot_attachment_id', $id))
            ->first(['id', 'personnel_id', 'visibility']);
        if ($media) {
            return [(int) $media->getAttribute('personnel_id'), ProfessionalPortfolioPermissionMatrix::mediaViewPermissions(), $media->getAttribute('visibility') === 'restricted'];
        }

        $project = PersonnelProjectRecord::query()
            ->where('evidence_attachment_id', $id)
            ->first(['id', 'personnel_id']);
        if ($project) {
            return [(int) $project->getAttribute('personnel_id'), ProfessionalPortfolioPermissionMatrix::projectViewPermissions(), false];
        }

        return [null, [], false];
    }
}
