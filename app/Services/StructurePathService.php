<?php

namespace App\Services;

use App\Models\Structure;

/**
 * Ancestor labels for a structure, without a query per org level.
 *
 * Walking `->parent` (lazily, or via the `withRecursive('parent')` scope) costs one round
 * trip per level — seven on the current chart — every time a page renders a unit label.
 * The org chart is small, so one flat read answers every lookup the request makes.
 *
 * Bind this `scoped()`: a fresh instance per caller re-reads the chart, which on a table
 * means once per row.
 */
class StructurePathService
{
    /**
     * @var array<int, array{name:string, parent_id:int|null}>|null
     */
    protected ?array $structureMap = null;

    /**
     * @var array<string, list<string>>
     */
    protected array $segmentCache = [];

    /**
     * The unit and its ancestors, outermost first.
     *
     * Callers join these themselves: unit names contain spaces, so a joined string cannot
     * be split back apart.
     *
     * @return list<string>
     */
    public function segments(?int $structureId, bool $includeRoot = false): array
    {
        if (empty($structureId)) {
            return [];
        }

        $structureId = (int) $structureId;
        $key = $structureId.($includeRoot ? ':root' : '');

        if (array_key_exists($key, $this->segmentCache)) {
            return $this->segmentCache[$key];
        }

        $map = $this->structureMap();
        $names = [];
        $cursor = $structureId;
        // A malformed parent chain must not spin forever.
        $guard = count($map) + 1;

        while (isset($map[$cursor]) && $guard-- > 0) {
            $node = $map[$cursor];

            // The organizational root is a label most screens already carry in the header.
            if ($node['parent_id'] === null && ! $includeRoot) {
                break;
            }

            $names[] = $node['name'];

            if ($node['parent_id'] === null) {
                break;
            }

            $cursor = (int) $node['parent_id'];
        }

        return $this->segmentCache[$key] = array_values(array_reverse(array_filter($names)));
    }

    /**
     * The full chain as one label. Kept for the screens that show the whole path.
     */
    public function resolve(?int $structureId): string
    {
        return implode(' / ', $this->segments($structureId));
    }

    /**
     * Just the unit the person actually sits in — what a table column should print, with
     * resolve() available for the hover title.
     */
    public function current(?int $structureId): string
    {
        $segments = $this->segments($structureId);

        return $segments === [] ? '' : (string) end($segments);
    }

    public function resolveFromModel(?Structure $structure): string
    {
        return $this->resolve($structure?->id);
    }

    /**
     * @return array<int, array{name:string, parent_id:int|null}>
     */
    protected function structureMap(): array
    {
        if ($this->structureMap !== null) {
            return $this->structureMap;
        }

        return $this->structureMap = Structure::query()
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn (Structure $structure) => [
                (int) $structure->id => [
                    'name' => (string) $structure->name,
                    'parent_id' => $structure->parent_id !== null ? (int) $structure->parent_id : null,
                ],
            ])
            ->all();
    }
}
