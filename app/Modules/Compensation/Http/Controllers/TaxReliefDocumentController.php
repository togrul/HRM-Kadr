<?php

namespace App\Modules\Compensation\Http\Controllers;

use App\Models\EmployeeTaxRelief;
use App\Models\User;
use App\Services\StructureService;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Güzəşt kartının təsdiqedici sənədi (özəl disk). Yalnız kompensasiyaya baxış icazəsi olan və işçinin
 * strukturunu görən istifadəçi açır.
 */
class TaxReliefDocumentController
{
    public function __invoke(EmployeeTaxRelief $relief): StreamedResponse
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->can('show-compensation'), 403);
        abort_unless(app(StructureService::class)->allowsPersonnel($user, $relief->personnel()->first(['id', 'tabel_no', 'structure_id'])), 403);

        $disk = PrivateFiles::locate($relief->document_path, $relief->document_disk);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk($disk, (string) $relief->document_path, $relief->document_name, true);
    }
}
