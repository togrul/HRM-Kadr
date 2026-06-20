<?php

namespace App\Modules\Candidates\Http\Controllers;

use App\Models\CandidateDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CandidateDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, CandidateDocument $document)
    {
        Gate::authorize('view', $document->candidate);

        if ($request->boolean('inline')) {
            return Storage::disk($document->disk)->response(
                $document->file_path,
                $document->original_name,
                [],
                'inline'
            );
        }

        return Storage::disk($document->disk)->download(
            $document->file_path,
            $document->original_name
        );
    }
}
