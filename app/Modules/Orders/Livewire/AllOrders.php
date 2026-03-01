<?php

namespace App\Modules\Orders\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Cache;
use App\Services\Orders\OrderPrintPayloadFactory;
use App\Services\Orders\OrderTemplateRenderer;
use App\Services\StructureService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
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
            __('#'),
            __('Order #'),
            __('Type'),
            __('Given date'),
            __('Given by'),
            __('Status'),
            'action',
            'action',
            'action',
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
        $this->dispatch('orderAdded', __('Order was updated successfully!'));
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

        $this->dispatch('orderWasDeleted', __('Order was deleted!'));
    }

    public function printOrder(string $order_no)
    {
        $order = OrderLog::with(['order', 'components', 'attributes'])->where('order_no', $order_no)->first();
        if (! $order || ! $order->order) {
            abort(404);
        }

        $this->authorize('view', $order->order);
        $payload = app(OrderPrintPayloadFactory::class)->build($order);

        $outputPath = app(OrderTemplateRenderer::class)->render(
            storedTemplatePath: (string) $payload['template_path'],
            scalarValues: (array) $payload['scalar_values'],
            rows: (array) $payload['rows'],
            outputBaseName: (string) $payload['output_base_name'],
            context: (array) $payload['context'],
        );

        return response()->download($outputPath, basename($outputPath))->deleteFileAfterSend();
    }

    protected function returnData($type = 'normal')
    {
        $globalOrderIds = Order::globalVisibilityOrderIds();

        $result = OrderLog::query()
            ->with([
                'order:id,name,blade',
                'status:id,name',
                'orderType:id,name',
            ])
            ->when($this->status === 'deleted', fn ($query) => $query->with('personDidDelete:id,name'))
            ->where(function ($query) use ($globalOrderIds) {
                if ($globalOrderIds !== []) {
                    $query->whereIn('order_id', $globalOrderIds)
                        ->orWhere(function ($innerQuery) use ($globalOrderIds) {
                            $innerQuery->whereNotIn('order_id', $globalOrderIds)
                                ->whereHas('personnels', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $this->accessibleStructureIds));
                        });

                    return;
                }

                $query->whereHas('personnels', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $this->accessibleStructureIds));
            })
            ->filter($this->search ?? [])
            ->when($this->selectedOrder, fn($q) => $q->where('order_id', $this->selectedOrder))
            ->when(is_numeric($this->status), fn($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn($q) => $q->onlyTrashed())
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

    #[Isolate]
    public function getStatusesProperty()
    {
        $locale = config('app.locale');

        return Cache::remember("order_statuses:{$locale}", now()->addMinutes(10), function () use ($locale) {
            return OrderStatus::where('locale', $locale)->get();
        });
    }

    public function mount(StructureService $structureService)
    {
        $this->authorize('viewAny', Order::class);
        $this->fillFilter();
        $this->selectedOrder = $this->selectedOrder ?? request()->query('selectedOrder');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    public function render()
    {
        $orders = $this->returnData();

        return view('orders::livewire.orders.all-orders', compact('orders'));
    }
}
