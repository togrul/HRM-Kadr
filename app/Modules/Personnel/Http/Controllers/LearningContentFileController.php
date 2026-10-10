<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\EmployeeContentAsset;
use App\Services\UserPersonnelLinkResolver;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningContentFileController
{
    public function __invoke(Request $request, EmployeeContentAsset $asset, UserPersonnelLinkResolver $links): StreamedResponse
    {
        $user = $request->user();
        $personnelId = $links->resolve($user);
        $isAssignee = $personnelId !== null && $user->can('view-own-learning-content')
            && $asset->assignments()->where('personnel_id', $personnelId)->exists();
        abort_unless($isAssignee || $user->canAny([
            'view-learning-library', 'manage-employee-content-library', 'assign-employee-content',
        ]), 403);

        $path = (string) $asset->getAttribute('storage_path');
        if (in_array($asset->getAttribute('storage_disk'), [null, '', 'employee_content'], true)) {
            $path = 'employee-content/'.$path;
        }
        $disk = PrivateFiles::locate($path, $asset->getAttribute('storage_disk'));
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk($disk, $path, $asset->getAttribute('title'), inline: true);
    }
}
