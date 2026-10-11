<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Application\Services\Personnel360ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Əməkdaş 360 zaman xəttinin PDF/XLSX ixracı. Marşrut `can:export-personnel-360` ilə
 * qapılıdır; burada əlavə olaraq əməkdaşın baxanın struktur görünürlüyündə olması
 * (PersonnelPolicy::view) yoxlanır. Həssas hadisələr servisdə icazəyə görə redaksiya olunur.
 */
class Personnel360ExportController
{
    public function __invoke(Request $request, Personnel $personnel, string $format, Personnel360ExportService $exports): BinaryFileResponse
    {
        $viewer = $request->user();
        abort_unless($viewer instanceof User && Gate::forUser($viewer)->allows('view', $personnel), 403);

        $filters = [
            'type' => $request->query('type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => is_string($request->query('search')) ? mb_substr((string) $request->query('search'), 0, 120) : null,
        ];

        try {
            return $format === 'pdf'
                ? $exports->pdf($personnel, $viewer, $filters)
                : $exports->xlsx($personnel, $viewer, $filters);
        } catch (RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }
    }
}
