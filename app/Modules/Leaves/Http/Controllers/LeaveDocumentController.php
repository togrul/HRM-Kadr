<?php

namespace App\Modules\Leaves\Http\Controllers;

use App\Models\Leave;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İcazəyə əlavə olunmuş sənədi (xəstəlik vərəqəsi, ərizə…) yalnız icazəli istifadəçiyə verir:
 * icazəni görə bilən (LeavePolicy::view) və ya icazənin sahibi olan əməkdaş.
 * Sənəd brauzerdə yalnız PDF/şəkil olduqda açılır, qalanı endirilir.
 */
class LeaveDocumentController
{
    public function __invoke(Request $request, int $leave): StreamedResponse
    {
        $record = Leave::query()->withTrashed()->findOrFail($leave);
        $user = $request->user();

        abort_unless(
            Gate::allows('view', $record) || PrivateFiles::isOwnTabelNo($user, (string) $record->getAttribute('tabel_no')),
            403
        );

        $path = (string) $record->getAttribute('document_path');
        $disk = PrivateFiles::locate($path);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk($disk, $path, basename($path), inline: true);
    }
}
