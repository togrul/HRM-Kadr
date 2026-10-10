<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use App\Support\Language\AzerbaijaniDateFormatter;
use Closure;
use Illuminate\Support\Collection;

/**
 * The project lists a manual order field can be bound to. When the author marks a
 * template variable as one of these types, the composer shows a dropdown of the
 * corresponding records and the chosen record's name is written into the document as
 * plain text. Add an entry here to expose a new bindable list — nothing else changes.
 *
 * Some lists depend on the order being composed (an order revocation lists only the
 * chosen employee's approved orders): their options closure receives the composer's
 * context (['personnel_id' => …]).
 */
class OrderLookupFieldRegistry
{
    /** Option ids of the rest-day work compensation list. */
    public const REST_DAY_COMPENSATION_DOUBLE_PAY = 1;

    public const REST_DAY_COMPENSATION_DAY_OFF = 2;

    /** Option ids of the business-trip kind list, and the codes the trip register stores. */
    public const TRIP_TYPE_DOMESTIC = 1;

    public const TRIP_TYPE_FOREIGN = 2;

    private const TRIP_TYPE_CODES = [
        self::TRIP_TYPE_DOMESTIC => 'domestic',
        self::TRIP_TYPE_FOREIGN => 'foreign',
    ];

    /** An order revocation offers at most this many of the employee's latest approved orders. */
    private const APPROVED_ORDER_LIMIT = 200;

    /** @var array<string,array<int,array{id:int,label:string,depth:int}>> per-request option cache */
    private array $optionCache = [];

    /**
     * @return array<string,array{label:string,options:Closure,resolve:Closure}>
     */
    private function definitions(): array
    {
        $restDayCompensation = [
            self::REST_DAY_COMPENSATION_DOUBLE_PAY => __('orders::order_composer.rest_day_compensation.double_pay'),
            self::REST_DAY_COMPENSATION_DAY_OFF => __('orders::order_composer.rest_day_compensation.day_off'),
        ];

        $tripTypes = [
            self::TRIP_TYPE_DOMESTIC => __('orders::order_composer.trip_type.domestic'),
            self::TRIP_TYPE_FOREIGN => __('orders::order_composer.trip_type.foreign'),
        ];

        return [
            'structure' => [
                'label' => __('orders::order_composer.field_types.structure'),
                // Hierarchical: parents before children, each with a depth for indentation.
                'options' => fn () => $this->structureTree(),
                'resolve' => fn ($id) => optional(Structure::find((int) $id))->name,
            ],
            'position' => [
                'label' => __('orders::order_composer.field_types.position'),
                'options' => fn () => $this->flat(Position::query()->orderBy('name')->pluck('name', 'id')->all()),
                'resolve' => fn ($id) => optional(Position::find((int) $id))->name,
            ],
            'rank' => [
                'label' => __('orders::order_composer.field_types.rank'),
                'options' => fn () => $this->flat(Rank::query()->where('is_active', true)->get()->pluck('name', 'id')->all()),
                'resolve' => fn ($id) => optional(Rank::find((int) $id))->name,
            ],
            'personnel' => [
                'label' => __('orders::order_composer.field_types.personnel'),
                'options' => fn () => $this->flat(Personnel::query()
                    ->where('is_pending', false)
                    ->whereNull('leave_work_date')
                    ->orderBy('surname')->orderBy('name')
                    ->get(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
                    ->mapWithKeys(fn (Personnel $p): array => [$p->id => trim($p->surname.' '.$p->name.' '.$p->patronymic).' ('.$p->tabel_no.')'])
                    ->all()),
                'resolve' => function ($id): ?string {
                    $person = ctype_digit((string) $id) ? Personnel::query()->find((int) $id, ['surname', 'name', 'patronymic']) : null;

                    return $person ? trim($person->surname.' '.$person->name.' '.$person->patronymic) : null;
                },
            ],
            'approved_order' => [
                'label' => __('orders::order_composer.field_types.approved_order'),
                'options' => fn (array $context = []) => $this->approvedOrders($context),
                'resolve' => fn ($id) => $this->orderReference($id),
            ],
            'rest_day_compensation' => [
                'label' => __('orders::order_composer.field_types.rest_day_compensation'),
                'options' => fn () => $this->flat($restDayCompensation),
                'resolve' => fn ($id) => $restDayCompensation[(int) $id] ?? null,
            ],
            'trip_type' => [
                'label' => __('orders::order_composer.field_types.trip_type'),
                'options' => fn () => $this->flat($tripTypes),
                'resolve' => fn ($id) => $tripTypes[array_search(self::tripTypeCode($id), self::TRIP_TYPE_CODES, true) ?: 0] ?? null,
            ],
        ];
    }

    /**
     * The register code ('domestic' / 'foreign') of a trip-kind field value — its option id,
     * or the code itself; null when it is neither.
     */
    public static function tripTypeCode(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if (ctype_digit($value)) {
            return self::TRIP_TYPE_CODES[(int) $value] ?? null;
        }

        return in_array($value, self::TRIP_TYPE_CODES, true) ? $value : null;
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
     * @param  array<string,mixed>  $context  the order being composed (e.g. personnel_id)
     * @return array<int,array{id:int,label:string,depth:int}>
     */
    public function options(string $type, array $context = []): array
    {
        $key = $type.'|'.json_encode($context);

        if (isset($this->optionCache[$key])) {
            return $this->optionCache[$key];
        }

        $def = $this->definitions()[$type] ?? null;

        return $this->optionCache[$key] = $def ? (array) ($def['options'])($context) : [];
    }

    /**
     * The chosen employee's approved Word-engine orders, latest first — the orders an
     * order revocation may cancel. Revocation orders themselves are left out (no chains).
     *
     * @param  array<string,mixed>  $context
     * @return array<int,array{id:int,label:string,depth:int}>
     */
    private function approvedOrders(array $context): array
    {
        $personnelId = (int) ($context['personnel_id'] ?? 0);

        if ($personnelId <= 0) {
            return [];
        }

        $revocationCodes = OrderWordTemplate::query()->where('effect', 'order_cancellation')->pluck('code')->all();

        return OrderLog::query()
            ->where('template_render_mode', OrderIssueService::RENDER_MODE_DOCX)
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->where('template_snapshot->personnel_id', $personnelId)
            ->when($revocationCodes !== [], fn ($query) => $query->whereNotIn('template_snapshot->template_code', $revocationCodes))
            ->orderByDesc('id')
            ->limit(self::APPROVED_ORDER_LIMIT)
            ->get(['id', 'order_no', 'given_date', 'template_snapshot'])
            ->map(fn (OrderLog $order): array => [
                'id' => (int) $order->id,
                'label' => '№ '.$order->order_no.' · '.(optional($order->given_date)->format('d.m.Y') ?? '—').' · '.data_get($order->template_snapshot, 'label', ''),
                'depth' => 0,
            ])
            ->values()
            ->all();
    }

    /**
     * How the document names a revoked order: "08.10.2026-cı il tarixli 100-M nömrəli
     * “Ezamiyyət”".
     */
    private function orderReference(mixed $id): ?string
    {
        $order = ctype_digit((string) $id) ? OrderLog::query()->find((int) $id, ['id', 'order_no', 'given_date', 'template_snapshot']) : null;

        if ($order === null) {
            return null;
        }

        $date = $order->given_date ? app(AzerbaijaniDateFormatter::class)->longDate($order->given_date) : '';

        return trim(__('orders::order_composer.lookup.order_reference', [
            'date' => $date,
            'number' => (string) $order->order_no,
            'label' => (string) data_get($order->template_snapshot, 'label', ''),
        ]));
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
