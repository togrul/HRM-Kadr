<?php

namespace App\Modules\Orders\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderLog;
use App\Modules\Orders\Domain\Contracts\AccessibleStructureScopeReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['orderAdded', 'orderWasDeleted'])]
class AllOrders extends Component
{
    use AuthorizesRequests, SideModalAction, WithPagination;

    protected OrderTypeStatusLookupReadRepository $orderTypeStatusLookup;

    public $selectedOrder;

    #[Url]
    public $status;

    #[Url]
    public $search = [];

    #[Locked]
    public array $accessibleStructureIds = [];

    #[On('selectOrder')]
    public function selectOrder($id): void
    {
        $this->selectedOrder = $id;
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
            __('orders::order_list.table.row_no'),
            __('orders::order_list.table.order_no'),
            __('orders::order_list.table.type'),
            __('orders::order_list.table.given_date'),
            __('orders::order_list.table.given_by'),
            __('orders::order_list.table.status'),
            __('orders::order_list.table.action'),
            __('orders::order_list.table.action'),
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

        // Only block-engine orders are printable: they carry their frozen HTML in
        // the snapshot, so the final .docx renders straight from it. Legacy orders
        // are no longer generated and have no downloadable document.
        abort_unless((string) $order->template_render_mode === \App\Services\Orders\Document\OrderIssueService::RENDER_MODE, 404);
        abort_unless((bool) auth()->user()?->can('add-orders'), 403);

        $html = (string) data_get($order->template_snapshot, 'html', '');
        abort_if($html === '', 404);
        $path = app(\App\Services\Orders\Document\OrderHtmlToDocxRenderer::class)->renderToFile($html);

        return response()->download($path, $order->order_no.'.docx')->deleteFileAfterSend();
    }

    public function approveOrder(string $order_no): void
    {
        $order = OrderLog::where('order_no', $order_no)->first();
        if (! $order) {
            return;
        }

        abort_unless((bool) auth()->user()?->can('add-orders'), 403);

        app(\App\Services\Orders\Document\Effects\BlockOrderApprovalService::class)->approve($order);

        $this->dispatch('orderAdded', __('orders::order_composer.messages.order_approved'));
    }

    protected function returnData($type = 'normal')
    {
        $globalOrderIds = Order::globalVisibilityOrderIds();

        $result = OrderLog::query()
            ->with([
                'order:id,name',
                'status:id,name',
                'orderType:id,name',
            ])
            ->when($this->status === 'deleted', fn ($query) => $query->with('personDidDelete:id,name'))
            ->where(function ($query) use ($globalOrderIds) {
                // Globally-visible legacy orders OR orders whose personnel sit in an
                // accessible structure. orWhereHas (not whereNotIn) so block-engine
                // orders with a null order_id are included rather than dropped by
                // SQL's "NULL NOT IN (...)".
                $query->when(
                    $globalOrderIds !== [],
                    fn ($q) => $q->whereIn('order_id', $globalOrderIds)
                )->orWhereHas('personnels', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $this->accessibleStructureIds));
            })
            ->filter($this->search ?? [])
            ->when($this->selectedOrder, fn ($q) => $q->where('order_id', $this->selectedOrder))
            ->when(is_numeric($this->status), fn ($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->orderByDesc('given_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(20)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (OrderLog $order, int $index) use ($start) {
                $order->row_no = $start + $index + 1;
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

    #[Isolate]
    public function getStatusesProperty()
    {
        $locale = config('app.locale');

        return Cache::remember(
            "order_statuses:{$locale}",
            now()->addMinutes(10),
            fn () => $this->orderTypeStatusLookup->localizedStatuses((string) $locale)
        );
    }

    public function mount(
        AccessibleStructureScopeReadRepository $accessibleStructureScopeReadRepository,
        OrderTypeStatusLookupReadRepository $orderTypeStatusLookup
    ) {
        $this->authorize('viewAny', Order::class);
        $this->orderTypeStatusLookup = $orderTypeStatusLookup;
        $this->fillFilter();
        $this->selectedOrder = $this->selectedOrder ?? request()->query('selectedOrder');
        $this->accessibleStructureIds = $accessibleStructureScopeReadRepository->accessibleStructureIds();
    }

    public function render()
    {
        return view('orders::livewire.orders.all-orders');
    }
}
