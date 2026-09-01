<?php

namespace App\Modules\Orders\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderLog;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Exports\OrderExport;
use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\StructureService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
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

    public function fillFilter(): void
    {
        $this->status = request()->query('status') ?? 'all';
    }

    public function resetFilter()
    {
        $this->reset('search');
        $this->resetPage();
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

    public function setDeleteOrder($order_no)
    {
        $this->dispatch('setDeleteOrder', $order_no);
    }

    #[Renderless]
    public function restoreData($order_no)
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
    public function forceDeleteData($order_no)
    {
        $model = OrderLog::withTrashed()->where('order_no', $order_no)->first();

        if (! $model) {
            return;
        }

        $this->authorize('forceDelete', $model);

        $model->handleDeletion();

        $this->dispatch('orderWasDeleted', __('orders::order_form.messages.order_deleted'));
    }

    public function printOrder(string $order_no)
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            abort(404);
        }

        // Only Word-engine orders are printable: they carry their filled .docx.
        abort_unless((string) $order->template_render_mode === \App\Services\Orders\Document\OrderIssueService::RENDER_MODE_DOCX, 404);
        abort_unless((bool) auth()->user()?->can('add-orders'), 403);

        // Order numbers may contain "/" (e.g. 2026/ƏM-145), which is illegal in a
        // download filename — fold path separators to a dash.
        $safeName = str_replace(['/', '\\'], '-', (string) $order->order_no);

        $docxPath = (string) data_get($order->template_snapshot, 'docx_path', '');
        abort_unless($docxPath !== '' && \Illuminate\Support\Facades\Storage::disk('local')->exists($docxPath), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($docxPath, $safeName.'.docx');
    }

    public function approveOrder(string $order_no): void
    {
        $this->changeStatus($order_no, 'approve', 'order_approved');
    }

    public function cancelOrder(string $order_no): void
    {
        $this->changeStatus($order_no, 'cancel', 'order_cancelled');
    }

    public function reopenOrder(string $order_no): void
    {
        $this->changeStatus($order_no, 'reopen', 'order_reopened');
    }

    public function revertOrder(string $order_no): void
    {
        $this->changeStatus($order_no, 'revert', 'order_reverted');
    }

    /**
     * Run a guarded status transition (approve/cancel/reopen/revert) on a Word-engine
     * order, surfacing any domain error (illegal jump, irreversible hire) to the user.
     */
    private function changeStatus(string $order_no, string $action, string $successKey): void
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            return;
        }

        abort_unless((bool) auth()->user()?->can('add-orders'), 403);

        try {
            app(\App\Services\Orders\Document\OrderStatusTransitionService::class)->{$action}($order);
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
                        ->where('template_render_mode', \App\Services\Orders\Document\OrderIssueService::RENDER_MODE_DOCX)
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

    protected function returnData($type = 'normal')
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
                $order->status_color_id = match ((int) $order->status_id) {
                    20 => 70,
                    30 => 90,
                    default => (int) $order->status_id,
                };

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
    public function getStatusesProperty()
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
    ) {
        $this->authorize('viewAny', Order::class);
        $this->fillFilter();
        $this->selectedOrder = $this->selectedOrder ?? request()->query('selectedOrder');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    public function render()
    {
        return view('orders::livewire.orders.all-orders');
    }
}
