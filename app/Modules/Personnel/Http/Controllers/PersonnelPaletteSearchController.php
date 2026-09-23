<?php

namespace App\Modules\Personnel\Http\Controllers;

use App\Models\Personnel;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Services\StructureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Feeds the ⌘K palette's people section: a handful of employees the user may see,
 * matched by name, tabel number or FİN, each linking straight to the personnel file.
 */
class PersonnelPaletteSearchController
{
    public function __invoke(Request $request, PersonnelQueryService $query, StructureService $structures): JsonResponse
    {
        Gate::authorize('viewAny', Personnel::class);

        $term = mb_substr(trim((string) $request->query('q', '')), 0, 60);

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = $query->quickFind($term, $structures->getAccessibleStructures())
            ->map(fn (Personnel $personnel): array => [
                'id' => $personnel->id,
                'name' => $personnel->fullname,
                'tabel_no' => (string) $personnel->tabel_no,
                'position' => (string) ($personnel->position?->name ?? ''),
                'left' => $personnel->leave_work_date !== null,
                'url' => route('personnel.show', $personnel->id),
            ])
            ->values();

        return response()->json(['results' => $results]);
    }
}
