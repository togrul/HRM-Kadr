<?php

namespace App\Modules\Vacation\Application\Services;

/**
 * Bir iş ili üzrə məzuniyyət hüququnun tərkibi (ƏM m.136): əsas + staj + uşaqlı valideyn +
 * əmək şəraiti. `exclusive` — işçi m.118–121 kateqoriyasındadır, əlavələr verilmir.
 */
final class EntitlementBreakdown
{
    public function __construct(
        public readonly int $base,
        public readonly int $seniority = 0,
        public readonly int $children = 0,
        public readonly int $conditions = 0,
        public readonly ?int $seniorityYears = null,
        public readonly bool $exclusive = false,
        public readonly string $strategy = 'civil',
    ) {}

    public function total(): int
    {
        return $this->base + $this->seniority + $this->children + $this->conditions;
    }

    /**
     * @return array{base:int,seniority:int,children:int,conditions:int,seniority_years:?int,exclusive:bool,strategy:string,total:int}
     */
    public function toArray(): array
    {
        return [
            'base' => $this->base,
            'seniority' => $this->seniority,
            'children' => $this->children,
            'conditions' => $this->conditions,
            'seniority_years' => $this->seniorityYears,
            'exclusive' => $this->exclusive,
            'strategy' => $this->strategy,
            'total' => $this->total(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data, int $fallbackTotal = 0, string $strategy = 'civil'): self
    {
        if (! is_array($data) || ! isset($data['base'])) {
            return new self($fallbackTotal, strategy: $strategy);
        }

        return new self(
            (int) $data['base'],
            (int) ($data['seniority'] ?? 0),
            (int) ($data['children'] ?? 0),
            (int) ($data['conditions'] ?? 0),
            isset($data['seniority_years']) ? (int) $data['seniority_years'] : null,
            (bool) ($data['exclusive'] ?? false),
            (string) ($data['strategy'] ?? $strategy),
        );
    }
}
