<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Services\UserPersonnelLinkResolver;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Onboarding sənəd şablonunun faylını verir: onboarding kitabxanasını görən / idarə edən
 * istifadəçiyə və ya həmin sənəd özünə təyin edilmiş əməkdaşa.
 */
class OnboardingTemplateFileController
{
    public const VIEW_PERMISSIONS = ['view-onboarding-library', 'manage-onboarding-document-templates', 'assign-onboarding-documents'];

    public function __invoke(Request $request, OnboardingDocumentTemplate $template, UserPersonnelLinkResolver $links): StreamedResponse
    {
        $user = $request->user();
        $personnelId = $links->resolve($user);

        $isAssignee = $personnelId !== null && OnboardingDocumentAssignment::query()
            ->where('template_id', $template->getKey())
            ->where('personnel_id', $personnelId)
            ->exists();

        abort_unless($isAssignee || (bool) $user?->canAny(self::VIEW_PERMISSIONS), 403);

        $disk = PrivateFiles::locate($template->file_path, $template->disk);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk(
            $disk,
            (string) $template->file_path,
            $template->getAttribute('title') ?: basename((string) $template->file_path),
            inline: true,
        );
    }
}
