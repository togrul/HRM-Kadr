<?php

namespace App\Modules\Orders\Livewire;

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
    use AuthorizesRequests;
    use OrderCrud;

    public $orderModelData;

    private const DATE_FORMAT = 'd.m.Y';

    protected function fillOrder()
    {
        $orderLog = $this->fetchOrderData();
        if (! $orderLog) {
            abort(404);
        }

        $this->authorize('update', $orderLog);

        $this->orderModelData = $orderLog;

        $this->initializeOrderData();

        $this->originalComponents = match ($this->selectedBlade) {
            'default' => $this->componentForms,
            'vacation','business-trips' => $this->selectedPersonnel->rows,
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
            $this->selectedPersonnel->personnels = $this->orderModelData->personnels->pluck('tabel_no')->all();
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
            'default' => $this->componentForms,
            'vacation', 'business-trips' => $this->selectedPersonnel->rows,
        };
    }

    private function setOrderBasicData(): void
    {
        $this->orderForm->fillFromModel($this->orderModelData);
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

        $this->componentForms[$rowNumber]['component_id'] = $component->id;

        foreach ($attributes->attributes as $ka => $attr) {
            $this->processAttribute($rowNumber, $ka, $attr);
        }

        $this->setSelectedPersonnelList();
    }

    private function setSelectedPersonnelList(): void
    {
        if ($this->isSpecialBlade()) {
            $this->selectedPersonnel->rows = $this->mapPersonnelAttributes();
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
            $this->componentForms[$rowNumber][$colData['columnName']] = $colData['columnValue'];
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
            $columnValue = $attr['value'];
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
        $payload = $this->orderForm->payload();

        DB::transaction(function () use ($data, $payload) {
            $this->updateOrder($payload);
            $this->manageComponentsAndAttributes(data: $data, orderPayload: $payload);
        });

        $this->dispatch('orderAdded', __('Order was updated successfully!'));

        return null;
    }

    private function updateOrder(array $payload): void
    {
        $this->orderModelData->update([
            'order_type_id' => $payload['order_type_id'],
            'order_id' => $payload['order_id'],
            'order_no' => $payload['order_no'],
            'given_date' => Carbon::parse($payload['given_date'])->format('Y-m-d'),
            'given_by' => $payload['given_by'],
            'given_by_rank' => $payload['given_by_rank'],
            'description' => $payload['description'],
            'status_id' => $payload['status_id'],
        ]);
    }

    private function manageComponentsAndAttributes(array $data, array $orderPayload): void
    {
        $this->componentPersister->sync($this->orderModelData, $data['component_ids'], true);

        //get attributes and insert to attributes table
        $this->saveAttribute($this->orderModelData, $data['attributes'], 'update');

        $assignedTabels = [];

        if ($this->isDefaultBlade()) {
            $assignedTabels = $this->handleDefaultBladePersonnel($data, $orderPayload);
        } else {
            $assignedTabels = $this->handleSpecialBladePersonnel($data);
        }

        $confirmedPayload = $this->isCandidateOrder()
            ? ($assignedTabels['candidate_ids'] ?? [])
            : ($assignedTabels['tabels'] ?? $assignedTabels);

        (new OrderConfirmedService($this->orderModelData))->handle($confirmedPayload, 'update');
    }

    private function handleDefaultBladePersonnel(array $data, array $orderPayload): array
    {
        return $this->personnelPersister->attachFromVacancies(
            $this->orderModelData,
            $data['vacancy_list'],
            $this->isCandidateOrder(),
            $orderPayload['status_id']
        );
    }

    private function handleSpecialBladePersonnel(array $data): array
    {
        $componentIds = collect($this->fillPersonnelsToComponents($this->orderModelData->order->blade))
            ->values()
            ->pluck('component_id')
            ->all();

        $this->personnelPersister->syncAssignments(
            $this->orderModelData,
            $this->selectedPersonnel->personnels,
            $componentIds
        );

        return [
            'tabels' => $this->selectedPersonnel->personnels,
            'candidate_ids' => [],
        ];
    }
}
