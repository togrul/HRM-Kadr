<?php

namespace App\Services\Orders\Variables;

use App\Models\Personnel;
use App\Support\Language\AzerbaijaniDeclension;

/**
 * Resolves the `employee.*` variable namespace from a selected personnel, including
 * the Azerbaijani grammatical-case variants order clauses need (dative/genitive of
 * the name, genitive/dative of the possessive-marked position & structure names).
 *
 * Phase 2 of the order-engine redesign — the single, declension-aware replacement
 * for the old scattered ResolvesPersonnelVariables concern. The name cases are
 * reliable; position/structure cases assume the (near-universal) possessive form
 * and are otherwise fixed by the HR user in the order preview.
 */
class OrderEmployeeVariableResolver
{
    public function __construct(private readonly AzerbaijaniDeclension $declension) {}

    /**
     * @return array<string,string>
     */
    public function resolve(?Personnel $personnel): array
    {
        if (! $personnel) {
            return [];
        }

        $surname = trim((string) $personnel->surname);
        $name = trim((string) $personnel->name);
        $patronymic = trim((string) $personnel->patronymic);
        $genderSuffix = (int) ($personnel->gender ?? 0) === 2 ? 'qızı' : 'oğlu';

        $fullName = trim(implode(' ', array_filter([$surname, $name, $patronymic])));
        $fullNameWithSuffix = trim($fullName.' '.$genderSuffix);
        $initials = $this->initials($surname, $name, $patronymic);

        $position = trim((string) ($personnel->position?->name ?? ''));
        $structure = trim((string) ($personnel->structure?->name ?? ''));

        return [
            'employee.full_name' => $fullName,
            'employee.full_name_with_suffix' => $fullNameWithSuffix,
            'employee.full_name_dative' => $fullName === '' ? '' : $this->declension->nameDative($fullNameWithSuffix),
            'employee.full_name_genitive' => $fullName === '' ? '' : $this->declension->nameGenitive($fullNameWithSuffix),
            'employee.initials' => $initials,
            'employee.initials_genitive' => $initials === '' ? '' : $this->declension->declineName($initials, 'genitive'),
            'employee.surname' => $surname,
            'employee.name' => $name,
            'employee.patronymic' => $patronymic,
            'employee.gender_suffix' => $genderSuffix,
            'employee.tabel_no' => (string) ($personnel->tabel_no ?? ''),
            'employee.position' => $position,
            'employee.position_genitive' => $position === '' ? '' : $this->declension->possessiveGenitive($position),
            'employee.position_dative' => $position === '' ? '' : $this->declension->possessiveDative($position),
            'employee.structure' => $structure,
            'employee.structure_genitive' => $structure === '' ? '' : $this->declension->possessiveGenitive($structure),
            'employee.structure_dative' => $structure === '' ? '' : $this->declension->possessiveDative($structure),
        ];
    }

    /** Build the "Name.Patronymic.Surname" initials form, e.g. "R.B.Bayramov". */
    private function initials(string $surname, string $name, string $patronymic): string
    {
        $prefix = implode('.', array_filter([
            $this->firstLetter($name),
            $this->firstLetter($patronymic),
        ]));

        if ($surname === '') {
            return $prefix;
        }

        return $prefix === '' ? $surname : $prefix.'.'.$surname;
    }

    private function firstLetter(string $word): string
    {
        $word = trim($word);

        return $word === '' ? '' : mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
    }
}
