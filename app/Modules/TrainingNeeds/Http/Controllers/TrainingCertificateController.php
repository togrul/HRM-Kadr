<?php

namespace App\Modules\TrainingNeeds\Http\Controllers;

use App\Models\TrainingDeliveryRecord;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Təlim sertifikatını yalnız təlim ehtiyaclarını görə bilən istifadəçiyə (Dashboard-dakı
 * eyni yoxlama) və ya sertifikatın sahibi olan əməkdaşa verir.
 */
class TrainingCertificateController
{
    public const VIEW_PERMISSIONS = ['show-training-needs', 'manage-training-needs', 'review-training-needs', 'export-training-needs'];

    public function __invoke(Request $request, int $record, HrPolicyPackService $policies): StreamedResponse
    {
        $delivery = TrainingDeliveryRecord::query()->findOrFail($record);
        $user = $request->user();

        $canView = $policies->permissionEnabled('training_needs.view') && (bool) $user?->canAny(self::VIEW_PERMISSIONS);
        abort_unless($canView || PrivateFiles::isOwnPersonnel($user, (int) $delivery->getAttribute('personnel_id')), 403);

        $path = (string) $delivery->getAttribute('certificate_path');
        $disk = PrivateFiles::locate($path);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk(
            $disk,
            $path,
            $delivery->getAttribute('certificate_name') ?: basename($path),
            $request->boolean('inline'),
        );
    }
}
