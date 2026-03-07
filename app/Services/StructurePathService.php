<?php

namespace App\Services;

use App\Models\Structure;

class StructurePathService
{
    /**
     * @var array<int, array{name:string, parent_id:int|null}>
     */
    protected ?array $structureMap = null;

    /**
     * @var array<int, string>
     */
    protected array $pathCache = [];

    public function resolve(?int $structureId): string
    {
        if (empty($structureId)) {
            return '';
        }

        $structureId = (int) $structureId;

        if (array_key_exists($structureId, $this->pathCache)) {
            return $this->pathCache[$structureId];
        }

        $map = $this->structureMap();
        $segments = [];
        $cursor = $structureId;

        while (isset($map[$cursor])) {
            $node = $map[$cursor];

            if ($node['parent_id'] === null) {
                break;
            }

            $segments[] = $node['name'];
            $cursor = (int) $node['parent_id'];
        }

        return $this->pathCache[$structureId] = implode(' / ', array_reverse($segments));
    }

    public function resolveFromModel(?Structure $structure): string
    {
        return $this->resolve($structure?->id);
    }

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
