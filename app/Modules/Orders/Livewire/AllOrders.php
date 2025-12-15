<?php

namespace App\Modules\Orders\Livewire;

use App\Helpers\UsefulHelpers;
use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Cache;
use App\Models\Structure;
use App\Services\GenerateWordReplaceContent;
use App\Services\StructureService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpWord\TemplateProcessor;

#[On(['orderAdded', 'orderWasDeleted'])]
class AllOrders extends Component
{
    use AuthorizesRequests, SideModalAction, WithPagination;

    public $selectedOrder;

    #[Url]
    public $status;

    #[Url]
    public $search = [];

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
        $givenDate = $order->given_date;
        $suffixService = new WordSuffixService;
        $bladeType = $order->order->blade;

        $templateProcessor = new TemplateProcessor('storage/' . $order->order->content);
        $templateProcessor->setValue('day', $givenDate->format('d'));
        $templateProcessor->setValue('month', $givenDate->locale('AZ')->monthName);
        $templateProcessor->setValue('year', $givenDate->format('Y'));
        $templateProcessor->setValue('rank_director', $order->given_by_rank);
        $templateProcessor->setValue('name_director', $order->given_by);

        switch ($bladeType) {
            case Order::BLADE_BUSINESS_TRIP:
                $endDate = Carbon::parse($order->description['end_date']);
                $startDate = Carbon::parse($order->description['start_date']);
                $startDateFormat = $startDate->format('d');

                if ($startDate->format('m') != $endDate->format('m')) {
                    $startDateFormat .= " {$startDate->locale('AZ')->monthName}";
                }

                if ($startDate->format('Y') != $endDate->format('Y')) {
                    $startDateFormat .= " {$startDate->format('Y')}";
                }
                $templateProcessor->setValue('location', $order->description['location']);
                $templateProcessor->setValue('day_start', $startDateFormat);
                $templateProcessor->setValue('day_end', $endDate->format('d'));
                $templateProcessor->setValue('month_trip', $endDate->locale('AZ')->monthName);
                $templateProcessor->setValue('year_trip', $endDate->format('Y') . $suffixService->getNumberSuffix($endDate->format('Y')));
                break;
        }

        $attributesByComponent = $order->attributes->load('component');

        if ($bladeType == Order::BLADE_BUSINESS_TRIP) {
            $_component_texts = $attributesByComponent->groupBy(function ($attribute) {
                $rowNumber = $attribute->attributes['row']['value'] ?? null;
                $transportation = $attribute->attributes['$transportation']['value'] ?? null;

                return "{$rowNumber}_{$transportation}";
            });
        } else {
            $_component_texts = $attributesByComponent->groupBy('row_number');
        }

        $_component_texts = $_component_texts->map(function ($group) {
            $component = $group->first()->component;

            return [
                'title' => $component->title,
                'content' => $group->pluck('component.content')->toArray(),
            ];
        })->toArray();

        $replacements = [];
        $_replace_texts = [];
        $globalIndex = 0;

        //                $att = $order->attributes->groupBy(function ($attribute) {
        //                    $structure = $attribute->attributes['$structure']['value'] ?? null;
        //
        //                    return "{$structure}";
        //                });
        //                dd($att->toArray(),$order->attributes);
        foreach ($order->components as $componentIndex => $_component) {
            $attr_list = $order->attributes
                ->where('component_id', $_component->id)
                ->where('row_number', $componentIndex)
                ->pluck('attributes')
                ->toArray();

            // pluck edib dovre salmaq olar.
            foreach ($attr_list as $attrIndex => $attr) {
                if ($bladeType == Order::BLADE_BUSINESS_TRIP) {
                    $keyReplaced = "{$attr['row']['value']}_{$attr['$transportation']['value']}";
                    $_replace_texts[$keyReplaced][] = UsefulHelpers::modifyArrayToKeyValuePair($attr);

                    $lastIndex = array_key_last($_replace_texts[$keyReplaced]);
                    $_replace_texts[$keyReplaced][$lastIndex]['order'] = $bladeType;
                } else {
                    $_replace_texts[] = UsefulHelpers::modifyArrayToKeyValuePair($attr);
                    $keyReplaced = $globalIndex++;
                    $_replace_texts[$keyReplaced]['order'] = $bladeType;
                }

                switch ($bladeType) {
                    case Order::BLADE_DEFAULT:
                        $_replace_texts[$keyReplaced]['$year'] .= $suffixService->getNumberSuffix((int) $_replace_texts[$keyReplaced]['$year']);
                        $_replace_texts[$keyReplaced]['$surname'] = $suffixService->getSurnameSuffix($_replace_texts[$keyReplaced]['$surname']);
                        $_replace_texts[$keyReplaced]['$structure_main'] = $suffixService->getStructureSuffix($_replace_texts[$keyReplaced]['$structure_main']);
                        $_replace_texts[$keyReplaced]['$fullname'] = $this->convertWordIntoBold($_replace_texts[$keyReplaced]['$fullname']);
                        break;
                    case Order::BLADE_VACATION:
                        $_replace_texts[$keyReplaced]['$start_date'] .= $suffixService->getNumberSuffix(Carbon::parse($_replace_texts[$keyReplaced]['$start_date'])->year);
                        $_replace_texts[$keyReplaced]['$end_date'] .= $suffixService->getNumberSuffix(Carbon::parse($_replace_texts[$keyReplaced]['$end_date'])->year);
                        $_replace_texts[$keyReplaced]['$structure'] = $this->getFullStructureNameWithSuffixes($_replace_texts[$keyReplaced]['$structure'], $suffixService);
                        $_replace_texts[$keyReplaced]['$position'] = $suffixService->getMultiSuffix($_replace_texts[$keyReplaced]['$position'], multi: false);
                        break;
                    case Order::BLADE_BUSINESS_TRIP:
                        $trip_start = Carbon::parse($_replace_texts[$keyReplaced][$lastIndex]['$start_date']);
                        $_replace_texts[$keyReplaced][$lastIndex]['$trip_start_day'] = $trip_start->format('d');
                        $_replace_texts[$keyReplaced][$lastIndex]['$trip_start_month'] = $trip_start->locale('AZ')->monthName;
                        $_replace_texts[$keyReplaced][$lastIndex]['$trip_start_year'] = $trip_start->year . $suffixService->getNumberSuffix($trip_start->year) . ' ' . __('year');
                        $_replace_texts[$keyReplaced][$lastIndex]['$trip_location'] = $_replace_texts[$keyReplaced][$lastIndex]['$location'];
                        $_replace_texts[$keyReplaced][$lastIndex]['$return_day'] = $suffixService->getMonthDaySuffix($_replace_texts[$keyReplaced][$lastIndex]['$return_day']);
                        $_replace_texts[$keyReplaced][$lastIndex]['$meeting_hour'] = $suffixService->getTimeSuffix($_replace_texts[$keyReplaced][$lastIndex]['$meeting_hour']);
                        break;
                }
            }
        }

        $secondIndex = 0;

        foreach ($_component_texts as $key => &$text) {
            ['content' => $content, 'title' => $title] = (new GenerateWordReplaceContent($bladeType, $_replace_texts))
                ->handle($key, $text, $secondIndex);
            $secondIndex++;
            $text = ! empty($title) ? $this->convertWordIntoBold($title) . PHP_EOL . '<w:p/>' . $content : $content;

            $replacements[] = [
                'content_text' => match ($bladeType) {
                    Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP => str_replace("\n", '<w:br/>', $text),
                    Order::BLADE_DEFAULT => ($key + 1) . '. ' . str_replace("\n", '<w:br/>', $text),
                },
            ];
        }

        $templateProcessor->replaceBlock('newline', PHP_EOL);
        $templateProcessor->cloneBlock('content', 0, true, false, $replacements);
        // end export to word file

        $filename = "{$order->order->name}_" . Carbon::now()->format('d.m.Y H:i:s');
        $templateProcessor->saveAs($filename . '.docx');

        return response()->download($filename . '.docx')->deleteFileAfterSend();
    }

    protected function getFullStructureNameWithSuffixes($name, $service)
    {
        $structureModel = Structure::where('name', $name)->first();
        $structureFullName = $structureModel->getAllParentName(isCoded: true);

        return collect($structureFullName)->map(
            fn($structure) => $service->getStructureSuffix($structure, mainStructure: true) . ' '
        )->implode('');
    }

    private function convertWordIntoBold(string $word): string
    {
        return '<w:rPr><w:b w:val="true"/></w:rPr>'
            . $word
            . '<w:rPr><w:b w:val="false"/></w:rPr>';
    }

    protected function returnData($type = 'normal')
    {
        $structureService = app(StructureService::class);
        $accessible = $structureService->getAccessibleStructures();
        $result = OrderLog::with([
            'order',
            'components',
            'status',
            'personDidDelete',
            'creator',
            'orderType',
        ])
           ->where(fn ($query) => $query
                ->where('order_id', 1010)
                ->orWhere(fn ($query) => $query
                    ->where('order_id', '!=', 1010)
                    ->whereHas('personnels', fn ($query) => $query->whereIn('structure_id', $accessible))
                )
            )
            ->filter($this->search ?? [])
            ->when($this->selectedOrder, fn($q) => $q->where('order_id', $this->selectedOrder))
            ->when(is_numeric($this->status), fn($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn($q) => $q->onlyTrashed())
            ->orderByDesc('given_date');

        return $type == 'normal'
            ? $result->paginate(20)->withQueryString()
            : $result->cursor();
    }

    #[Isolate]
    public function getStatusesProperty()
    {
        $locale = config('app.locale');

        return Cache::remember("order_statuses:{$locale}", now()->addMinutes(10), function () use ($locale) {
            return OrderStatus::where('locale', $locale)->get();
        });
    }

    public function mount()
    {
        $this->authorize('viewAny', Order::class);
        $this->fillFilter();
        $this->selectedOrder = $this->selectedOrder ?? request()->query('selectedOrder');
    }

    public function render()
    {
        $orders = $this->returnData();

        return view('orders::livewire.orders.all-orders', compact('orders'));
    }
}
