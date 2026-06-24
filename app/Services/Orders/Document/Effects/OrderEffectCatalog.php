<?php

namespace App\Services\Orders\Document\Effects;

/**
 * The single source of truth for the HR side-effects an order type can perform on
 * approval: each effect's label, its structured inputs (roles), and its handler class.
 *
 * Drives three things from one definition list, so the handler mapping can never drift
 * from the field structures:
 *   - the designer's effect picker / per-variable role dropdown (options(), roles());
 *   - building the effect's fields at approval (kinds(), isEffect());
 *   - resolving the handler that applies/reverses the effect (for()).
 */
class OrderEffectCatalog
{
    /**
     * Every effect kind with its UI label, structured roles, and handler class.
     * A null handler means the kind is selectable in the designer but its side-effect
     * is run elsewhere (e.g. 'hire' is handled directly by OrderStatusTransitionService).
     *
     * @return array<string,array{label:string,roles:array<int,array{key:string,label:string,type:string}>,handler:?class-string<OrderEffect>}>
     */
    private function definitions(): array
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
                'handler' => VacationEffect::class,
            ],
            'termination' => [
                'label' => 'Xitam (əmək müqaviləsi bitir)',
                'roles' => [
                    ['key' => 'date', 'label' => 'Xitam tarixi', 'type' => 'date'],
                ],
                'handler' => TerminationEffect::class,
            ],
            'transfer' => [
                'label' => 'Köçürmə (struktur/vəzifə dəyişir)',
                'roles' => [
                    ['key' => 'new_structure', 'label' => 'Yeni struktur', 'type' => 'structure'],
                    ['key' => 'new_position', 'label' => 'Yeni vəzifə', 'type' => 'position'],
                ],
                'handler' => TransferEffect::class,
            ],
            'surname_change' => [
                'label' => 'Soyad dəyişikliyi',
                'roles' => [
                    ['key' => 'new_surname', 'label' => 'Yeni soyad', 'type' => 'text'],
                ],
                'handler' => SurnameChangeEffect::class,
            ],
            'hire' => [
                'label' => 'İşə qəbul (namizəd işçi olur)',
                // Structure & position are dedicated hire inputs (they also drive the
                // document's employee.* variables); only the join date is a role here.
                // Hire has no OrderEffect handler — OrderStatusTransitionService converts
                // the candidate into an employee directly.
                'roles' => [
                    ['key' => 'start_date', 'label' => 'İşə qəbul tarixi', 'type' => 'date'],
                ],
                'handler' => null,
            ],
        ];
    }

    /**
     * @return array<string,array{label:string,roles:array<int,array{key:string,label:string,type:string}>}>
     */
    public function kinds(): array
    {
        return array_map(
            fn (array $def) => ['label' => $def['label'], 'roles' => $def['roles']],
            $this->definitions(),
        );
    }

    /**
     * Resolve the handler implementing an effect kind. Unmapped/none/handler-less kinds
     * return null (the order just changes status, with no OrderEffect side-effect).
     */
    public function for(string $effect): ?OrderEffect
    {
        $handler = $this->definitions()[$effect]['handler'] ?? null;

        return $handler ? app($handler) : null;
    }

    /**
     * Effect options for the designer selector (with the "no effect" default first).
     *
     * @return array<int,array{kind:string,label:string}>
     */
    public function options(): array
    {
        $options = [['kind' => 'none', 'label' => 'Yoxdur (yalnız sənəd)']];
        foreach ($this->definitions() as $kind => $def) {
            $options[] = ['kind' => $kind, 'label' => $def['label']];
        }

        return $options;
    }

    /**
     * @return array<int,array{key:string,label:string,type:string}>
     */
    public function roles(string $kind): array
    {
        return $this->definitions()[$kind]['roles'] ?? [];
    }

    public function isEffect(string $kind): bool
    {
        return $kind === 'none' || isset($this->definitions()[$kind]);
    }
}
