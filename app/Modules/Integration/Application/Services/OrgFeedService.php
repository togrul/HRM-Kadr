<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\Position;
use App\Models\Structure;
use App\Modules\Integration\Support\Contract;

/**
 * The organisation feeds: structure tree and positions.
 *
 * Small, slow-moving tables — but they are served through the same cursor
 * protocol as people rather than as one blob, so a consumer has a single way to
 * read everything and a first load cannot time out on a large tree.
 *
 * ## Codes, not names
 *
 * The counterpart stores department and position as immutable **codes** and
 * treats names as editable labels. A structure carries its own `code`; a
 * position does not, so its id is used. Both are stringified on purpose: what
 * crosses the wire is an identifier, and arithmetic on it would be a mistake.
 */
class OrgFeedService
{
    /**
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}
     */
    public function units(int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $rows = Structure::query()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'parent_id', 'name', 'shortname', 'level']);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return [
            'items' => $rows->map(fn (Structure $s): array => [
                'external_id' => (string) $s->id,
                'code' => $this->code($s->id),
                // Now that the code IS the id, the parent's code needs no lookup —
                // the old helper queried the database only to hand back the ids it
                // was already given.
                'parent_code' => $s->parent_id === null ? null : $this->code($s->parent_id),
                'name' => (string) $s->name,
                'short_name' => $this->text($s->shortname),
                'level' => (int) ($s->level ?? 0),
            ])->values()->all(),
            'last_sequence' => (int) ($rows->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}
     */
    public function positions(int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $rows = Position::query()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'name']);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return [
            'items' => $rows->map(fn (Position $p): array => [
                'external_id' => (string) $p->id,
                // No code column exists here; the id is the stable identifier.
                'code' => (string) $p->id,
                'name' => (string) $p->name,
            ])->values()->all(),
            'last_sequence' => (int) ($rows->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }

    /**
     * The code a unit is known by on the wire.
     *
     * This deliberately does NOT use `structures.code`. That column looks like
     * a business code but is a **sibling ordinal**: in real data 43 units carry
     * codes 1, 1, 1, 2, 1 … repeated at every level, with no unique index to
     * stop it. Sending it broke two things at once — the consumer's link table
     * rejected the duplicates, and, worse, `parent_code` became ambiguous, so
     * the rebuilt tree would have been wrong in a way nothing reported.
     *
     * The row id is unique, stable across reorganisation (a unit that moves
     * keeps its identity, and so does the history recorded against it), and
     * already what `external_id` carries. The unit's meaning travels in `name`.
     * `org.positions` has always keyed on the id for the same reason.
     */
    private function code(mixed $id): string
    {
        return (string) $id;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
