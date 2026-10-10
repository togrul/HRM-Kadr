<?php

namespace App\Modules\Candidates\Http\Controllers;

use App\Models\CandidateDocument;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateDocumentDownloadController extends Controller
{
    /**
     * Namizəd sənədini qaytarır. Brauzerdə yalnız PDF/PNG/JPEG/WebP açılır; digər tiplər
     * (o cümlədən köhnə SVG/HTML yükləmələri) həmişə endirmə kimi göndərilir.
     */
    public function __invoke(Request $request, CandidateDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document->candidate);

        return SecureFileResponse::fromDisk(
            (string) $document->disk,
            (string) $document->file_path,
            (string) $document->original_name,
            $request->boolean('inline'),
        );
    }
}
