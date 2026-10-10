<?php

namespace App\Services;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * İstifadəçinin struktur görünürlüyü: ya «bütün strukturlar» (rolun açıq bayrağı), ya da
 * konkret struktur id-lərinin siyahısı. Boş siyahı «heç nə» deməkdir — fail closed.
 * Əvvəllər boş massiv həm «hamısı», həm «heç biri» mənasında işlənirdi; bu obyekt həmin
 * qarışıqlığı aradan qaldırır.
 */
final class StructureScope
{
    /**
     * @param  list<int>  $ids
     */
    private function __construct(
        private readonly bool $all,
        private readonly array $ids,
    ) {}

    public static function all(): self
    {
        return new self(true, []);
    }

    public static function none(): self
    {
        return new self(false, []);
    }

    /**
     * @param  iterable<int|string|null>  $ids
     */
    public static function of(iterable $ids): self
    {
        $normalized = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return new self(false, array_values($normalized));
    }

    /**
     * @param  array{all?: bool, ids?: list<int>}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return ($payload['all'] ?? false) ? self::all() : self::of($payload['ids'] ?? []);
    }

    /**
     * @return array{all: bool, ids: list<int>}
     */
    public function toArray(): array
    {
        return ['all' => $this->all, 'ids' => $this->ids];
    }

    public function isAll(): bool
    {
        return $this->all;
    }

    /** Heç bir struktur açıq deyil — istifadəçi heç bir işçi sətrini görməməlidir. */
    public function isNone(): bool
    {
        return ! $this->all && $this->ids === [];
    }

    /**
     * Məhdud siyahı. «Bütün strukturlar» halında boşdur — onu isAll() ilə yoxla.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return $this->ids;
    }

    /** Struktursuz (null) qeyd yalnız «bütün strukturlar» bayrağı olan rola görünür. */
    public function allows(int|string|null $structureId): bool
    {
        if ($this->all) {
            return true;
        }

        $structureId = (int) $structureId;

        return $structureId > 0 && in_array($structureId, $this->ids, true);
    }

    /**
     * @param  iterable<int|string|null>  $structureIds
     */
    public function allowsAll(iterable $structureIds): bool
    {
        foreach ($structureIds as $structureId) {
            if (! $this->allows($structureId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Müştəridən gələn struktur filtrini görünürlüklə kəsişdirir. Filtr boşdursa nəticə
     * görünürlüyün özüdür; filtr görünməyən struktur istəyirsə, o atılır.
     *
     * @param  iterable<int|string|null>|int|string|null  $requested
     */
    public function intersect(iterable|int|string|null $requested): self
    {
        if ($requested === null || $requested === '' || $requested === []) {
            return $this;
        }

        $requestedScope = self::of(is_iterable($requested) ? $requested : [$requested]);

        if ($requestedScope->isNone()) {
            return $this;
        }

        if ($this->all) {
            return $requestedScope;
        }

        return self::of(array_intersect($requestedScope->ids, $this->ids));
    }

    /**
     * Sorğunu görünürlüklə məhdudlaşdırır: «hamısı» heç nə əlavə etmir, boş siyahı
     * heç bir sətir qaytarmır.
     *
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function constrain(BuilderContract $query, string $column): BuilderContract
    {
        if ($this->all) {
            return $query;
        }

        if ($this->ids === []) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $query->whereIn($column, $this->ids);

        return $query;
    }

    /**
     * Əlaqə üzərindən məhdudlaşdırma (məs. məzuniyyət → işçi → structure_id). «Hamısı»
     * sorğunu dəyişmir; boş görünürlük heç nə qaytarmır.
     *
     * @template TBuilder of EloquentBuilder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function constrainThrough(EloquentBuilder $query, string $relation, string $column = 'structure_id'): EloquentBuilder
    {
        if ($this->all) {
            return $query;
        }

        if ($this->ids === []) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $query->whereHas($relation, fn ($related) => $related->whereIn($related->getModel()->qualifyColumn($column), $this->ids));

        return $query;
    }
}
