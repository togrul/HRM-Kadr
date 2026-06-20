<?php

namespace App\Services\Orders\Document\Effects;

/**
 * The catalog of HR side-effects an order type can perform on approval, and the
 * structured inputs each needs. Single source of truth for the designer's effect
 * picker / per-variable role dropdown AND for building the effect's fields at approval.
 */
class OrderEffectCatalog
{
    /**
     * @return array<string,array{label:string,roles:array<int,array{key:string,label:string,type:string}>}>
     */
    public function kinds(): array
    {
        return [
            'vacation' => [
                'label' => 'Məzuniyyət (işçi məzuniyyətə düşür)',
                'roles' => [
                    ['key' => 'start_date', 'label' => 'Başlama tarixi', 'type' => 'date'],
                    ['key' => 'end_date', 'label' => 'Bitmə tarixi', 'type' => 'date'],
                    ['key' => 'return_date', 'label' => 'İşə qayıtma tarixi', 'type' => 'date'],
                    ['key' => 'days', 'label' => 'Gün sayı', 'type' => 'number'],
                    ['key' => 'location', 'label' => 'Məzuniyyət yeri', 'type' => 'text'],
                ],
            ],
            'termination' => [
                'label' => 'Xitam (əmək müqaviləsi bitir)',
                'roles' => [
                    ['key' => 'date', 'label' => 'Xitam tarixi', 'type' => 'date'],
                ],
            ],
            'transfer' => [
                'label' => 'Köçürmə (struktur/vəzifə dəyişir)',
                'roles' => [
                    ['key' => 'new_structure', 'label' => 'Yeni struktur', 'type' => 'structure'],
                    ['key' => 'new_position', 'label' => 'Yeni vəzifə', 'type' => 'position'],
                ],
            ],
            'surname_change' => [
                'label' => 'Soyad dəyişikliyi',
                'roles' => [
                    ['key' => 'new_surname', 'label' => 'Yeni soyad', 'type' => 'text'],
                ],
            ],
            'hire' => [
                'label' => 'İşə qəbul (namizəd işçi olur)',
                // Structure & position are dedicated hire inputs (they also drive the
                // document's employee.* variables); only the join date is a role here.
                'roles' => [
                    ['key' => 'start_date', 'label' => 'İşə qəbul tarixi', 'type' => 'date'],
                ],
            ],
        ];
    }

    /**
     * Effect options for the designer selector (with the "no effect" default first).
     *
     * @return array<int,array{kind:string,label:string}>
     */
    public function options(): array
    {
        $options = [['kind' => 'none', 'label' => 'Yoxdur (yalnız sənəd)']];
        foreach ($this->kinds() as $kind => $def) {
            $options[] = ['kind' => $kind, 'label' => $def['label']];
        }

        return $options;
    }

    /**
     * @return array<int,array{key:string,label:string,type:string}>
     */
    public function roles(string $kind): array
    {
        return $this->kinds()[$kind]['roles'] ?? [];
    }

    public function isEffect(string $kind): bool
    {
        return $kind === 'none' || isset($this->kinds()[$kind]);
    }
}
