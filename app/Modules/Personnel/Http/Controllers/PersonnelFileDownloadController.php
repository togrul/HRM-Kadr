<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\Personnel;
use App\Models\PersonnelDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a personnel document only to users authorized to view that personnel.
 *
 * Personnel files used to be linked via Storage::url() on the public disk, i.e.
 * guessable, unauthenticated URLs to PII. They are now served through this gated
 * route; new uploads land on the private 'local' disk, and legacy files still on
 * 'public' are resolved transparently here.
 */
class PersonnelFileDownloadController
{
    public function __invoke(PersonnelDocument $document): StreamedResponse
    {
        $personnel = Personnel::withTrashed()
            ->where('tabel_no', $document->tabel_no)
            ->firstOrFail();

        Gate::authorize('view', $personnel);

        $path = (string) $document->file;
        $disk = $this->resolveDisk($path);
        abort_if($disk === null, 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = $document->filename
            ? trim((string) $document->filename).($extension !== '' ? '.'.$extension : '')
            : basename($path);

        return Storage::disk($disk)->download($path, $downloadName);
    }

    /**
     * New uploads live on 'local'; legacy uploads may still be on 'public'.
     */
    private function resolveDisk(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }
}
