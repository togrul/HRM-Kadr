<?php

namespace App\Modules\Orders\Livewire;

use App\Enums\OrderStatusEnum;
use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderLog;
use App\Modules\Orders\Application\Document\OrderTemplateProvider;
use App\Modules\Orders\Contracts\OrderDrafter;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Exports\OrderExport;
use App\Modules\Orders\Infrastructure\Document\OrderDeletionService;
use App\Modules\Orders\Infrastructure\Document\OrderFinalPdfService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Services\StructureService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read Collection $statuses Livewire computed (getStatusesProperty)
 */
#[On(['orderAdded', 'orderWasDeleted'])]
class AllOrders extends Component
{
    use AuthorizesRequests, SideModalAction, WithPagination;

    public $selectedOrder;

    #[Url]
    public $status;

    #[Url]
    public $search = [];

    #[Locked]
    public array $accessibleStructureIds = [];

    public function selectOrder($id): void
    {
        $this->selectedOrder = $id === '' ? null : $id;
        $this->resetPage();
    }

    public function setStatus($newStatus): void
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    /**
     * The status select binds straight to $status; anything outside the offered set
     * ('all', a status id, 'deleted' for admins) falls back to 'all'.
     */
    public function updatedStatus(mixed $value): void
    {
        $allowed = $this->statuses->pluck('id')->map(fn ($id): string => (string) $id)
            ->push('all')
            ->when(auth()->user()?->hasRole('Admin'), fn ($keys) => $keys->push('deleted'));

        if (! $allowed->contains((string) $value)) {
            $this->status = 'all';
        }

        $this->resetPage();
    }

    /**
     * The type select binds straight to $selectedOrder; its placeholder (null) means "all types".
     */
    public function updatedSelectedOrder(mixed $value): void
    {
        $this->selectOrder($value ?? '');
    }

    public function fillFilter(): void
    {
        $this->status = request()->query('status') ?? 'all';
    }

    public function resetFilter(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return collect(Arr::dot((array) $this->search))->contains(fn ($value): bool => filled($value));
    }

    public function getTableHeaders(): array
    {
        return [
            __('orders::order_list.table.order_no'),
            __('orders::order_list.table.type'),
            __('orders::order_list.table.given_date'),
            __('orders::order_list.table.given_by'),
            __('orders::order_list.table.status'),
            __('orders::order_list.table.action'),
        ];
    }

    /**
     * Soft-delete an order (the row menu's "Sil", confirmed in the global modal). An approved
     * order is refused with a message: it has to be cancelled first, which reverses its effect.
     */
    #[Renderless]
    public function deleteOrder(string $order_no, OrderDeletionService $deletions): void
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            return;
        }

        if (OrderDeletionService::isProtected($order)) {
            $this->dispatch('orderError', __('orders::order_list.messages.approved_not_deletable'));

            return;
        }

        $this->authorize('delete', $order);

        try {
            $deletions->softDelete($order);
        } catch (DomainException $e) {
            $this->dispatch('orderError', $e->getMessage());

            return;
        }

        $this->dispatch('orderWasDeleted', __('orders::order_form.messages.order_deleted'));
    }

    /** Copy a Word-engine order as a new draft the author then continues in the composer. */
    #[Renderless]
    public function duplicateOrder(string $order_no, OrderIssueService $issuer): void
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            return;
        }

        abort_unless((bool) auth()->user()?->can('add-orders'), 403);

        $issuer->duplicateWord($order);

        $this->dispatch('orderAdded', __('orders::order_list.messages.order_duplicated'));
    }

    /**
     * The list/preview status badge as [x-status color id, label]. A pending order with no
     * stored document is shown as a draft ("Qaralama"); a pending one with its document is
     * ready for approval.
     *
     * @return array{0:int,1:string}
     */
    public static function statusBadge(OrderLog $order): array
    {
        if (OrderIssueService::isDraft($order)) {
            return [10, __('orders::order_list.status.draft')];
        }

        $color = match ((int) $order->status_id) {
            10 => 20,
            20 => 70,
            30 => 90,
            default => (int) $order->status_id,
        };

        return [$color, (string) ($order->status->name ?? '—')];
    }

    #[Renderless]
    public function restoreData($order_no): void
    {
        $orderLog = OrderLog::withTrashed()->where('order_no', $order_no)->first();
        if (! $orderLog) {
            return;
        }

        $this->authorize('restore', $orderLog);

        $orderLog->restore();
        $orderLog->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('orderAdded', __('orders::order_form.messages.order_updated'));
    }

    #[Renderless]
    public function forceDeleteData($order_no, OrderDeletionService $deletions): void
    {
        $model = OrderLog::withTrashed()->where('order_no', $order_no)->first();

        if (! $model) {
            return;
        }

        if (OrderDeletionService::isProtected($model)) {
            $this->dispatch('orderError', __('orders::order_list.messages.approved_not_deletable'));

            return;
        }

        $this->authorize('forceDelete', $model);

        try {
            $deletions->forceDelete($model);
        } catch (DomainException $e) {
            $this->dispatch('orderError', $e->getMessage());

            return;
        }

        $this->dispatch('orderWasDeleted', __('orders::order_form.messages.order_deleted'));
    }

    public function printOrder(string $order_no): StreamedResponse
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            abort(404);
        }

        // Only Word-engine orders are printable: they carry their filled .docx.
        abort_unless((string) $order->template_render_mode === OrderIssueService::RENDER_MODE_DOCX, 404);
        abort_unless((bool) auth()->user()?->can('export-orders'), 403);

        // Order numbers may contain "/" (e.g. 2026/ƏM-145), which is illegal in a
        // download filename — fold path separators to a dash.
        $safeName = str_replace(['/', '\\'], '-', (string) $order->order_no);

        $docxPath = (string) data_get($order->template_snapshot, 'docx_path', '');
        abort_unless($docxPath !== '' && \Illuminate\Support\Facades\Storage::disk('local')->exists($docxPath), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($docxPath, $safeName.'.docx');
    }

    /**
     * Download an approved order as PDF: its immutable final copy, else one rendered now.
     * Same permission as the Word download.
     */
    public function downloadPdf(string $order_no, OrderFinalPdfService $finalPdf): ?BinaryFileResponse
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            abort(404);
        }

        abort_unless((string) $order->template_render_mode === OrderIssueService::RENDER_MODE_DOCX, 404);
        abort_unless((int) $order->status_id === OrderStatusEnum::APPROVED->value, 404);
        abort_unless((bool) auth()->user()?->can('export-orders'), 403);

        $pdf = $finalPdf->forDownload($order);
        if ($pdf === null) {
            $this->dispatch('orderError', __('orders::order_list.messages.pdf_unavailable'));

            return null;
        }

        $safeName = str_replace(['/', '\\'], '-', (string) $order->order_no).'.pdf';

        return response()->download($pdf['path'], $safeName, ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend($pdf['temporary']);
    }

    public function approveOrder(string $order_no): void
    {
        // A draft has no document yet — it is finished in the composer first.
        if (($order = OrderLog::where('order_no', $order_no)->first()) && OrderIssueService::isDraft($order)) {
            $this->dispatch('orderError', __('orders::order_list.messages.draft_not_ready'));

            return;
        }

        $this->changeStatus($order_no, 'approve', 'order_approved');
    }

    /** Cancel an order; cancelling an approved one needs revert-orders and a reason (from the confirm modal). */
    public function cancelOrder(string $order_no, string $reason = ''): void
    {
        $this->changeStatus($order_no, 'cancel', 'order_cancelled', $reason);
    }

    public function reopenOrder(string $order_no): void
    {
        $this->changeStatus($order_no, 'reopen', 'order_reopened');
    }

    /** Revert an approved order to pending: needs revert-orders and a reason (from the confirm modal). */
    public function revertOrder(string $order_no, string $reason = ''): void
    {
        $this->changeStatus($order_no, 'revert', 'order_reverted', $reason);
    }

    /**
     * Run a guarded status transition (approve/cancel/reopen/revert) on a Word-engine
     * order, surfacing any domain error (illegal jump, a hire whose employee already has
     * records, a missing reason, a closed pay period) to the user. Taking an order out of
     * the approved state is authorised by revert-orders; everything else by add-orders.
     */
    private function changeStatus(string $order_no, string $action, string $successKey, string $reason = ''): void
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            return;
        }

        if ((int) $order->status_id === OrderStatusEnum::APPROVED->value && in_array($action, ['cancel', 'revert'], true)) {
            $this->authorize('revert', $order);
        } else {
            abort_unless((bool) auth()->user()?->can('add-orders'), 403);
        }

        $transitions = app(OrderStatusTransitionService::class);

        try {
            in_array($action, ['cancel', 'revert'], true)
                ? $transitions->{$action}($order, $reason)
                : $transitions->{$action}($order);
        } catch (DomainException $e) {
            $this->dispatch('orderError', $e->getMessage());

            return;
        }

        $this->dispatch('orderAdded', __("orders::order_composer.messages.{$successKey}"));
    }

    /**
     * The visibility-scoped, search-filtered order query every list read shares:
     * the paginated table, the panel status counts and the panel type counts.
     *
     * @return Builder<OrderLog>
     */
    protected function baseQuery(): Builder
    {
        $globalOrderIds = Order::globalVisibilityOrderIds();

        return OrderLog::query()
            ->where(function ($query) use ($globalOrderIds) {
                // Globally-visible legacy orders OR orders whose personnel sit in an
                // accessible structure. orWhereHas (not whereNotIn) so block-engine
                // orders with a null order_id are included rather than dropped by
                // SQL's "NULL NOT IN (...)".
                $query->when(
                    $globalOrderIds !== [],
                    fn ($q) => $q->whereIn('order_id', $globalOrderIds)
                )->orWhereHas('personnels', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $this->accessibleStructureIds))
                    // Pending hire (işə qəbul) orders have no personnel attached until
                    // approval, so they would otherwise be invisible. Scope them by the
                    // target structure frozen in the order snapshot.
                    ->orWhere(fn ($q) => $q
                        ->where('template_render_mode', OrderIssueService::RENDER_MODE_DOCX)
                        ->whereIn('template_snapshot->hire_structure_id', $this->accessibleStructureIds));
            })
            ->filter($this->search ?? []);
    }

    /**
     * The base query narrowed to the selected order type, which the list and the
     * status counts both sit inside.
     *
     * @return Builder<OrderLog>
     */
    protected function scopedQuery(): Builder
    {
        return $this->baseQuery()->when($this->selectedOrder, function ($q) {
            // Legacy block orders are addressed by order id; Word-engine orders have
            // no order_id and are addressed by the template code in their snapshot.
            return Str::startsWith((string) $this->selectedOrder, 'tpl:')
                ? $q->where('template_snapshot->template_code', Str::after((string) $this->selectedOrder, 'tpl:'))
                : $q->where('order_id', $this->selectedOrder);
        });
    }

    protected function returnData($type = 'normal'): LengthAwarePaginator|LazyCollection
    {
        $result = $this->scopedQuery()
            ->with([
                'order:id,name',
                'status:id,name',
                'orderType:id,name',
            ])
            ->when($this->status === 'deleted', fn ($query) => $query->with('personDidDelete:id,name'))
            ->when(is_numeric($this->status), fn ($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->orderByDesc('given_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(20)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (OrderLog $order) {
                [$order->status_color_id, $order->status_label] = self::statusBadge($order);

                return $order;
            })
        );

        return $paginated;
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->returnData();
    }

    /**
     * Order counts per status for the contextual panel, keyed by status id plus
     * the two synthetic buckets the panel also offers ("all" and "deleted").
     *
     * @return array<array-key, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        // The selected type is the outer scope, so the status counts sit inside it;
        // the type counts (typeFilters) stay independent of the status filter.
        $perStatus = $this->scopedQuery()
            ->toBase()
            ->selectRaw('status_id, count(*) as aggregate')
            ->groupBy('status_id')
            ->pluck('aggregate', 'status_id');

        $counts = ['all' => (int) $perStatus->sum()];

        foreach ($perStatus as $statusId => $total) {
            $counts[(int) $statusId] = (int) $total;
        }

        if (auth()->user()?->hasRole('Admin')) {
            $counts['deleted'] = $this->scopedQuery()->onlyTrashed()->count();
        }

        return $counts;
    }

    /**
     * The panel's order-type filters: every Word-engine template (the current engine)
     * plus any legacy block type that still holds orders, each counted inside the
     * current visibility + search scope.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    #[Computed]
    public function typeFilters(): array
    {
        $byTemplate = $this->baseQuery()
            ->toBase()
            ->selectRaw('count(*) as aggregate')
            ->addSelect('template_snapshot->template_code as code')
            ->groupBy('template_snapshot->template_code')
            ->pluck('aggregate', 'code')
            // MySQL's json_extract keeps the JSON quoting, SQLite's does not.
            ->mapWithKeys(fn ($total, $code): array => [trim((string) $code, '"') => (int) $total])
            ->all();

        $filters = [];

        foreach (app(OrderTemplateProvider::class)->available() as $code => $label) {
            $filters[] = ['key' => 'tpl:'.$code, 'label' => $label, 'count' => $byTemplate[$code] ?? 0];
        }

        $byLegacy = $this->baseQuery()
            ->toBase()
            ->whereNotNull('order_id')
            ->selectRaw('order_id, count(*) as aggregate')
            ->groupBy('order_id')
            ->pluck('aggregate', 'order_id');

        if ($byLegacy->isNotEmpty()) {
            foreach (Order::query()->whereIn('id', $byLegacy->keys())->orderBy('name')->pluck('name', 'id') as $id => $label) {
                $filters[] = ['key' => (string) $id, 'label' => (string) $label, 'count' => (int) $byLegacy[$id]];
            }
        }

        return $filters;
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('viewAny', Order::class);
        abort_unless((bool) auth()->user()?->can('export-orders'), 403);

        return Excel::download(
            new OrderExport($this->returnData('excel')),
            'orders-'.Carbon::now()->format('d.m.Y H:i').'.xlsx'
        );
    }

    #[Isolate]
    public function getStatusesProperty(): Collection
    {
        $locale = config('app.locale');

        return Cache::remember(
            "order_statuses:{$locale}",
            now()->addMinutes(10),
            // Resolve the repository per-call: this computed runs on every Livewire
            // request, but mount() (where injected deps live) only runs on the first.
            fn () => app(OrderTypeStatusLookupReadRepository::class)->localizedStatuses((string) $locale)
        );
    }

    public function mount(
        StructureService $structureService
    ): void {
        $this->authorize('viewAny', Order::class);
        $this->fillFilter();
        $this->selectedOrder = $this->selectedOrder ?? request()->query('selectedOrder');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();

        // Deep link from the command palette / quick links: land with the composer open.
        // ?preset=<template code> lands with that order type already chosen (e.g. a vacation order).
        if (request()->boolean('create') && (auth()->user()?->can('add-orders') ?? false)) {
            $preset = (string) request()->query('preset', '');
            $this->openSideMenu('order-composer', null, $preset !== '' && app(OrderDrafter::class)->hasTemplate($preset) ? $preset : null);
            $this->forgetDeepLinkParams('create', 'preset');
        }
    }

    public function render(): View
    {
        return view('orders::livewire.orders.all-orders');
    }
}
