<?php

namespace App\Livewire\Orders;

use App\Livewire\Traits\OrderCrud;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\OrderConfirmedService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditOrder extends Component
{
    use AuthorizesRequests,OrderCrud;

    public $orderModelData;

    private const DATE_FORMAT = 'd.m.Y';

    protected function fillOrder()
    {
        $this->authorize('edit-orders', $this->orderModelData);

        if (! $this->orderModelData = $this->fetchOrderData()) {
            abort(403);
        }

        $this->initializeOrderData();

        $this->originalComponents = match ($this->selectedBlade) {
            'default' => $this->components,
            'vacation','business-trips' => $this->selected_personnel_list,
        };
    }

    private function fetchOrderData()
    {
        return OrderLog::with(['order', 'components', 'personnels', 'status', 'attributes'])
            ->where('order_no', $this->orderModel)
            ->first();
    }

    private function initializeOrderData(): void
    {
        $this->setOrderBasicData();
        $this->setComponentData();
        $this->processOrderAttributes();

        if ($this->isSpecialBlade()) {
            $this->selected_personnel_list['personnels'] = $this->orderModelData->personnels->pluck('tabel_no')->all();
        }

        $this->originalComponents = $this->determineOriginalComponents();
    }

    private function isSpecialBlade(): bool
    {
        return in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP]);
    }

    private function determineOriginalComponents(): array
    {
        return match ($this->selectedBlade) {
            'default' => $this->components,
            'vacation', 'business-trips' => $this->selected_personnel_list,
        };
    }

    private function setOrderBasicData(): void
    {
        $orderData = $this->orderModelData;
        $this->order = [
            'order_id' => $orderData->order_id,
            'order_no' => $orderData->order_no,
            'given_date' => Carbon::parse($orderData->given_date)->format(self::DATE_FORMAT),
            'given_by' => $orderData->given_by,
            'given_by_rank' => $orderData->given_by_rank,
            'status_id' => $orderData->status_id,
            'description' => $orderData->description,
            'order_type_id' => $orderData->order_type_id,
        ];
    }

    private function setComponentData(): void
    {
        $this->showComponent = $this->orderModelData->order_type_id > 0;
        $this->selectedTemplate = $this->orderModelData->order_type_id;
        $this->componentRows = $this->orderModelData->components->count();
        $this->selectedBlade = $this->orderModelData->order->blade;
    }

    private function processOrderAttributes(): void
    {
        $tabelNos = $this->getTabelNos();

        foreach ($this->orderModelData->attributes as $key => $attributes) {
            $this->processAttributeRow($attributes, $key, $tabelNos);
        }

        $this->setSelectedPersonnelList();
    }

    private function getTabelNos(): array
    {
        return $this->isDefaultBlade() ? [] : Personnel::query()
            ->selectRaw("CONCAT(surname,' ',name,' ',patronymic) as fullname, tabel_no")
            ->pluck('tabel_no', 'fullname')
            ->toArray();
    }

    private function processAttributeRow($attributes, $key, array $tabelNos): void
    {
        $this->selectedComponents[$key] = array_keys($attributes->attributes);

        $this->setComponentAttributes($attributes, $tabelNos);
    }

    private function setComponentAttributes($attributes, array $tabelNos): void
    {
        $rowNumber = $attributes->row_number;
        $component = $this->orderModelData->components[$rowNumber];

        $this->components[$rowNumber]['component_id'] = $component->id;

        foreach ($attributes->attributes as $ka => $attr) {
            $this->processAttribute($rowNumber, $ka, $attr);
        }

        $this->setSelectedPersonnelList();
    }

    private function setSelectedPersonnelList(): void
    {
        if ($this->isSpecialBlade()) {
            $this->selected_personnel_list = $this->mapPersonnelAttributes();
        }
    }

    private function mapPersonnelAttributes(): array
    {
        $tabelNos = $this->getTabelNos();

        return $this->orderModelData->attributes
            ->groupBy('row_number')
            ->map(fn ($items, $rowIndex) => $items->pluck('attributes')->map(
                fn ($att) => $this->transformAttribute($att, $tabelNos, $rowIndex)
            ))
            ->toArray();
    }

    private function transformAttribute(array $att, array $tabelNos, int $rowIndex): array
    {
        $transformed = collect($att)
            ->except(['$start_date', '$end_date', '$days'])
            ->mapWithKeys(fn ($value, $key) => [str_replace('$', '', $key) => $value['value']])
            ->toArray();

        return array_merge($transformed, [
            'row' => $rowIndex,
            'key' => $tabelNos[$transformed['fullname']] ?? null,
        ]);
    }

    private function processAttribute(int $rowNumber, string $key, array $attr): void
    {
        $colData = $this->formatAttributeValue($key, $attr);

        if ($this->isDefaultBlade() || $this->isValidComponentColumn($colData['columnName'])) {
            $this->components[$rowNumber][$colData['columnName']] = $colData['columnValue'];
        }
    }

    private function isDefaultBlade(): bool
    {
        return $this->selectedBlade === Order::BLADE_DEFAULT;
    }

    private function isValidComponentColumn(string $columnName): bool
    {
        return in_array($columnName, ['start_date', 'end_date', 'days', 'location', 'meeting_hour', 'return_day', 'return_month']);
    }

    public function formatAttributeValue(string $_replacedKey, array $attr): array
    {
        $key = str_replace('$', '', $_replacedKey);
        if ($this->selectedBlade == Order::BLADE_DEFAULT) {
            $key = $key == 'fullname' ? 'personnel' : $key;
        }
        $isIdOrSpecial = ! empty($attr['id']) || $attr['value'] === '---';

        $columnName = $isIdOrSpecial ? "{$key}_id" : $key;

        if ($isIdOrSpecial && method_exists($this, 'isDropdownField') && $this->isDropdownField($columnName)) {
            $columnValue = $attr['id'];
            if ($columnValue && property_exists($this, 'componentOptionLabels')) {
                $this->componentOptionLabels[$columnName][(int) $columnValue] = $attr['value'];
            }
        } else {
            $columnValue = $isIdOrSpecial
                ? ['id' => $attr['id'], 'name' => $attr['value']]
                : $attr['value'];
        }

        return [
            'columnName' => $columnName,
            'columnValue' => $columnValue,
        ];
    }

    public function store()
    {
        $data = $this->fillCrudData();
        if (! is_array($data)) {
            return $data;
        }

        DB::transaction(function () use ($data) {
            $this->updateOrder();
            $this->manageComponentsAndAttributes(data: $data);
        });

        $this->dispatch('orderAdded', __('Order was updated successfully!'));

        return null;
    }

    private function updateOrder(): void
    {
        $this->orderModelData->update([
            'order_type_id' => $this->order['order_type_id'],
            'order_id' => $this->order['order_id'],
            'order_no' => $this->order['order_no'],
            'given_date' => Carbon::parse($this->order['given_date'])->format('Y-m-d'),
            'given_by' => $this->order['given_by'],
            'given_by_rank' => $this->order['given_by_rank'],
            'description' => $this->order['description'],
            'status_id' => $this->order['status_id'],
        ]);
    }

    private function manageComponentsAndAttributes(array $data): void
    {
        $this->componentPersister->sync($this->orderModelData, $data['component_ids'], true);

        //get attributes and insert to attributes table
        $this->saveAttribute($this->orderModelData, $data['attributes'], 'update');

        if ($this->isDefaultBlade()) {
            $this->handleDefaultBladePersonnel($data);
        } else {
            $this->handleSpecialBladePersonnel($data);
        }

        (new OrderConfirmedService($this->orderModelData))->handle($data['personnel_ids'], 'update');
    }

    private function handleDefaultBladePersonnel(array $data): void
    {
        $this->personnelPersister->attachFromVacancies(
            $this->orderModelData,
            $data['vacancy_list'],
            $this->isCandidateOrder(),
            $this->order['status_id']
        );
    }

    private function handleSpecialBladePersonnel(array $data): void
    {
        $componentIds = collect($this->fillPersonnelsToComponents($this->orderModelData->order->blade))
            ->values()
            ->pluck('component_id')
            ->all();

        $this->personnelPersister->syncAssignments(
            $this->orderModelData,
            $this->selected_personnel_list['personnels'],
            $componentIds
        );
    }
}
