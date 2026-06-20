<?php

namespace App\Services\Orders\Document;

use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use Illuminate\Support\Collection;

/**
 * The project lists a manual order field can be bound to. When the author marks a
 * template variable as one of these types, the composer shows a dropdown of the
 * corresponding records and the chosen record's name is written into the document as
 * plain text. Add an entry here to expose a new bindable list — nothing else changes.
 */
class OrderLookupFieldRegistry
{
    /** @var array<string,array<int,array{id:int,label:string,depth:int}>> per-request option cache */
    private array $optionCache = [];

    /**
     * @return array<string,array{label:string,options:\Closure,resolve:\Closure}>
     */
    private function definitions(): array
    {
        return [
            'structure' => [
                'label' => 'Struktur (siyahıdan)',
                // Hierarchical: parents before children, each with a depth for indentation.
                'options' => fn () => $this->structureTree(),
                'resolve' => fn ($id) => optional(Structure::find((int) $id))->name,
            ],
            'position' => [
                'label' => 'Vəzifə (siyahıdan)',
                'options' => fn () => $this->flat(Position::query()->orderBy('name')->pluck('name', 'id')->all()),
                'resolve' => fn ($id) => optional(Position::find((int) $id))->name,
            ],
            'rank' => [
                'label' => 'Rütbə (siyahıdan)',
                'options' => fn () => $this->flat(Rank::query()->where('is_active', true)->get()->pluck('name', 'id')->all()),
                'resolve' => fn ($id) => optional(Rank::find((int) $id))->name,
            ],
        ];
    }

    /**
     * The bindable list types for the designer's field-type picker.
     *
     * @return array<int,array{type:string,label:string}>
     */
    public function types(): array
    {
        $types = [];
        foreach ($this->definitions() as $type => $def) {
            $types[] = ['type' => $type, 'label' => $def['label']];
        }

        return $types;
    }

    public function isLookup(string $type): bool
    {
        return isset($this->definitions()[$type]);
    }

    /**
     * Options for the searchable picker: each {id, label, depth}. depth indents
     * hierarchical lists (structures); flat lists use depth 0.
     *
     * @return array<int,array{id:int,label:string,depth:int}>
     */
    public function options(string $type): array
    {
        if (isset($this->optionCache[$type])) {
            return $this->optionCache[$type];
        }

        $def = $this->definitions()[$type] ?? null;

        return $this->optionCache[$type] = $def ? (array) ($def['options'])() : [];
    }

    /**
     * @param  array<int,string>  $idToName
     * @return array<int,array{id:int,label:string,depth:int}>
     */
    private function flat(array $idToName): array
    {
        $out = [];
        foreach ($idToName as $id => $name) {
            $out[] = ['id' => (int) $id, 'label' => (string) $name, 'depth' => 0];
        }

        return $out;
    }

    /**
     * Structures flattened in tree order (parent immediately before its children),
     * each carrying its depth, siblings sorted by name.
     *
     * @return array<int,array{id:int,label:string,depth:int}>
     */
    private function structureTree(): array
    {
        /** @var Collection<int,Structure> $all */
        $all = Structure::query()->get(['id', 'name', 'parent_id']);
        $byParent = $all->groupBy(fn ($s) => $s->parent_id ?? 0);
        $ids = $all->pluck('id')->flip();

        $out = [];
        $walk = function ($parentKey, int $depth) use (&$walk, $byParent, &$out): void {
            foreach (($byParent[$parentKey] ?? collect())->sortBy('name') as $node) {
                $out[] = ['id' => (int) $node->id, 'label' => (string) $node->name, 'depth' => $depth];
                $walk($node->id, $depth + 1);
            }
        };

        // Roots = null parent, plus any node whose parent isn't in the set (orphans).
        $walk(0, 0);
        foreach ($all as $node) {
            if ($node->parent_id !== null && ! $ids->has($node->parent_id) && ! collect($out)->firstWhere('id', $node->id)) {
                $out[] = ['id' => (int) $node->id, 'label' => (string) $node->name, 'depth' => 0];
            }
        }

        return $out;
    }

    public function resolve(string $type, mixed $id): string
    {
        $def = $this->definitions()[$type] ?? null;
        if (! $def || $id === '' || $id === null) {
            return '';
        }

        return (string) (($def['resolve'])($id) ?: $id);
    }
}
