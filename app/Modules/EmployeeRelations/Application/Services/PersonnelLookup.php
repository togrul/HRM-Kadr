<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\Personnel;
use App\Models\Structure;
use App\Models\User;
use App\Services\StructureService;

/**
 * İş formalarında işçi seçimi: yalnız istifadəçinin struktur görünürlüyündəki aktiv işçilər.
 */
class PersonnelLookup
{
    public function __construct(private readonly StructureService $structures) {}

    /**
     * @return list<array{id:int, name:string, meta:string}>
     */
    public function search(User $user, string $term, int $limit = 8): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $query = Personnel::query()->active()->with('structure:id,name');
        $this->structures->scopeFor($user)->constrain($query, 'personnels.structure_id');

        return $query
            ->where(fn ($inner) => $inner
                ->where('surname', 'like', $term.'%')
                ->orWhere('name', 'like', $term.'%')
                ->orWhere('tabel_no', 'like', $term.'%'))
            ->orderBy('surname')
            ->limit($limit)
            ->get(['id', 'surname', 'name', 'patronymic', 'tabel_no', 'structure_id'])
            ->map(function (Personnel $personnel): array {
                $structure = $personnel->getRelation('structure');

                return [
                    'id' => (int) $personnel->id,
                    'name' => trim($personnel->fullname),
                    'meta' => trim($personnel->tabel_no.' · '.($structure instanceof Structure ? $structure->name : '')),
                ];
            })
            ->all();
    }

    /** Görünən işçinin tam adı; görünmürsə 403. */
    public function visibleName(User $user, int $personnelId): string
    {
        abort_unless($this->structures->allowsPersonnelId($user, $personnelId), 403);

        $personnel = Personnel::query()->select(['id', 'surname', 'name', 'patronymic'])->find($personnelId);
        abort_if($personnel === null, 404);

        return trim($personnel->fullname);
    }
}
