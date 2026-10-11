<?php

namespace App\Modules\EmployeeRelations\Http\Controllers;

use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\EvidenceService;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvidence;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sübut faylı yalnız işi görə bilən istifadəçiyə (CaseAccess — struktur görünürlüyü,
 * təyinat, sahib) verilir. Görünməyən iş üçün 404 qaytarılır ki, mövcudluğu da
 * açılmasın. Hər yükləmə audit jurnalına yazılır.
 */
class EvidenceDownloadController
{
    public function __invoke(Request $request, int $evidence, CaseAccess $access, EvidenceService $service): StreamedResponse
    {
        $user = $request->user();
        $record = RelationCaseEvidence::query()->with('case')->findOrFail($evidence);

        abort_if($record->case === null || ! $access->canView($user, $record->case), 404);

        $path = (string) $record->file_path;
        $disk = PrivateFiles::locate($path, PrivateFiles::DISK);
        abort_if($path === '' || $disk === null, 404);

        $service->logDownload($record, $user);

        return SecureFileResponse::fromDisk($disk, $path, $record->file_name ?: basename($path), $request->boolean('inline'));
    }
}
