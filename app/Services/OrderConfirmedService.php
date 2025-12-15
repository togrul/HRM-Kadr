<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Candidate;
use App\Models\Personnel;
use App\Enums\OrderStatusEnum;
use App\Helpers\UsefulHelpers;
use Illuminate\Support\Facades\DB;
use App\Events\StaffScheduleUpdated;

class OrderConfirmedService
{
    public function __construct(public OrderLog $orderLog, public array $extraData = []) {}

    public function handle($personnelIds = [], $action = 'create'): void
    {
        $this->orderLog->load('order');
        $statusId = $this->orderLog->status_id;
        $order = $this->orderLog->order;

        switch ($statusId) {
            case OrderStatusEnum::APPROVED->value:
                $this->handleApprovedOrder($personnelIds, $action, $order);
                break;
            case OrderStatusEnum::CANCELLED->value:
                $this->handleCancelledOrder($order);
                break;
        }
    }

    private function handleApprovedOrder(array $personnelIds, string $action, $order): void
    {
        // eger ise girme emridirse ve statusu tesdiqlenmisdirse
        if ($this->orderLog->order_id == Order::IG_EMR) {
            $candidateIds = $this->resolveCandidateIds($personnelIds);
            $this->approveEmploymentOrder($candidateIds, $this->orderLog, $action);
        }

        switch ($order->blade) {
            case Order::BLADE_VACATION:
                $this->processVacationOrder($this->orderLog);
                break;

            case Order::BLADE_BUSINESS_TRIP:
                $this->processBusinessTripsOrder($this->orderLog);
                break;
        }
    }

    private function handleCancelledOrder($order): void
    {
        switch ($order->blade) {
            case Order::BLADE_VACATION:
                $this->removeVacationPersonnels($this->orderLog);
                break;

            case Order::BLADE_BUSINESS_TRIP:
                $this->removeBusinessTripsPersonnels($this->orderLog);
                break;
        }
    }

    private function approveEmploymentOrder(array $candidateIds, $orderLog, $action): void
    {
        // Change status to accepted
        if (! empty($candidateIds)) {
            Candidate::whereIn('id', $candidateIds)->update(['status_id' => 70]);
        }

        $tabelNos = array_map(fn($x) => "NMZD{$x}", $candidateIds);

        $personnelModel = $action == 'create'
            ? $orderLog->personnels
            : Personnel::whereIn('tabel_no', $tabelNos)
            ->where('is_pending', true)
            ->with(['ranks', 'structure', 'position', 'laborActivities'])
            ->get();

        foreach ($personnelModel as $_personnel) {
            $this->updatePersonnelEmployment($_personnel, $orderLog);
        }
    }

    private function resolveCandidateIds(array $payload): array
    {
        $ids = [];

        foreach ($payload as $value) {
            // Already a numeric candidate id
            if (is_numeric($value)) {
                $ids[] = (int) $value;
                continue;
            }

            // Tabel no pattern NMZD123
            if (is_string($value) && preg_match('/NMZD(\d+)/', $value, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        if (! empty($ids)) {
            return array_values(array_unique($ids));
        }

        // Fallback: infer from attached personnels
        $this->orderLog->loadMissing('personnels');
        foreach ($this->orderLog->personnels as $personnel) {
            if (preg_match('/NMZD(\d+)/', (string) $personnel->tabel_no, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        return array_values(array_unique($ids));
    }

    private function updatePersonnelEmployment($_personnel, $orderLog): void
    {
        $component_id = $orderLog->personnels()
            ->where('order_log_personnels.tabel_no', $_personnel->tabel_no)
            ->value('component_id');

        $_attr = $orderLog->attributes()
            ->where('component_id', $component_id)
            ->value('attributes');

        $month = UsefulHelpers::convertToMonthNumber($_attr['$month']['value'], config('app.locale'));

        $date = "{$_attr['$year']['value']}-{$month}-{$_attr['$day']['value']}";

        DB::transaction(function () use ($_personnel, $date, $_attr, $orderLog) {
            $_personnel->update([
                'join_work_date' => $date,
                'is_pending' => false,
            ]);

            $_personnel->ranks()->create([
                'rank_id' => $_attr['$rank']['id'] ?? 10,
                'name' => 'İşə qəbul',
                'given_date' => $date,
                'order_no' => $orderLog->order_no,
                'order_date' => Carbon::parse($orderLog->given_date)->format('Y-m-d'),
                'order_given_by' => "{$orderLog->given_by_rank} {$orderLog->given_by}",
            ]);

            event(new StaffScheduleUpdated(
                structure_id: $_personnel->structure_id,
                position_id: $_personnel->position_id
            ));

            $this->endCurrentLaborActivity($_personnel, $date);

            $_personnel->laborActivities()->create($this->getNewLaborActivityData($_personnel, $orderLog, $date));
        });
    }

    private function endCurrentLaborActivity($_personnel, $date): void
    {
        $activeWork = $_personnel->laborActivities()
            ->where('is_current', true)
            ->whereNull('leave_date')
            ->first();

        if ($activeWork) {
            $activeWork->update([
                'leave_date' => $date,
                'is_current' => false,
            ]);
        }
    }

    private function getNewLaborActivityData($_personnel, $orderLog, $date): array
    {
        return [
            'company_name' => config('app.company'),
            'position' => $_personnel->position->name,
            'coefficient' => $_personnel->structure->coefficient,
            'join_date' => $date,
            'is_special_service' => true,
            'order_given_by' => "{$orderLog->given_by_rank} {$orderLog->given_by}",
            'order_no' => $orderLog->order_no,
            'order_date' => $orderLog->given_date,
            'is_current' => true,
        ];
    }

    private function processVacationOrder($orderLog): void
    {
        $orderLog->load('personnels');
        $orderAttributes = $orderLog->attributes->pluck('attributes')->toArray();
        foreach ($orderLog->personnels as $key => $_person) {
            $this->updateOrCreateVacation($_person, $orderAttributes, $key, $orderLog);
        }
    }

    private function processBusinessTripsOrder($orderLog): void
    {
        $orderLog->load(['personnels', 'attributes']);
        $orderAttributes = $orderLog->attributes->pluck('attributes')->toArray();
        foreach ($orderLog->personnels as $key => $_person) {
            $this->updateOrCreateBusinessTrips($_person, $orderAttributes, $key, $orderLog);
        }
    }

    private function updateOrCreateVacation($_person, $orderAttributes, $key, $orderLog): void
    {
        $this->updateOrCreateRecord($_person, $orderAttributes, $key, $orderLog);
    }

    private function updateOrCreateBusinessTrips($_person, $orderAttributes, $key, $orderLog): void
    {
        $this->updateOrCreateRecord($_person, $orderAttributes, $key, $orderLog);
    }

    private function updateOrCreateRecord($_person, $orderAttributes, $key, $orderLog): void
    {
        $type = $orderLog->order->blade;
        $arr = UsefulHelpers::searchInsideMultiDimensionalArray($orderAttributes, $_person->fullname, '$fullname', 'value');

        $commonAttributes = [
            'order_no' => $orderLog->order_no,
        ];

        $relationshipMethod = '';

        $filteredAttributes = $this->filterArrayByFullname($orderAttributes, $_person->fullname, 'fullname', true);

        $spesificAttributes = [
            'start_date' => Carbon::parse($arr[$key]['$start_date']['value'])->format('Y-m-d'),
            'end_date' => Carbon::parse($arr[$key]['$end_date']['value'])->format('Y-m-d'),
            'order_given_by' => "{$orderLog->given_by_rank} {$orderLog->given_by}",
            'order_no' => $orderLog->order_no,
            'order_date' => Carbon::parse($orderLog->given_date)->format('Y-m-d'),
            'attributes' => reset($filteredAttributes),
            'deleted_by' => null,
            'deleted_at' => null,
        ];

        switch ($type) {
            case Order::BLADE_VACATION:
                $spesificAttributes['vacation_places'] = $arr[$key]['$location']['value'];
                $spesificAttributes['duration'] = $arr[$key]['$days']['value'];
                $spesificAttributes['return_work_date'] = Carbon::parse($arr[$key]['$end_date']['value'])->addDay()->format('Y-m-d');
                // teze days lari elave etmek
                unset($spesificAttributes['attributes']);
                $extraData = $this->filterArrayByFullname($this->extraData[$key], $_person->fullname, 'fullname');
                $extraDataConverted = array_merge(...$extraData);
                $spesificAttributes['vacation_days_total'] = $extraDataConverted['vacation_days_total'];
                $spesificAttributes['remaining_days'] = $extraDataConverted['vacation_days_remaining'] - $spesificAttributes['duration'];
                $relationshipMethod = 'vacations';
                break;
            case Order::BLADE_BUSINESS_TRIP:
                $spesificAttributes['location'] = $arr[$key]['$location']['value'];
                $spesificAttributes['description'] = $orderLog->description['description'] ?? '';

                $relationshipMethod = 'businessTrips';
                break;
            default:
                throw new \InvalidArgumentException("Unsupported order type: $type");
        }
        $_person->{$relationshipMethod}()->withTrashed()->updateOrCreate(
            $commonAttributes,
            $spesificAttributes
        );

        if ($type === Order::BLADE_VACATION) {
            $_person->yearlyVacation()
                ->where('year', Carbon::parse($arr[$key]['$start_date']['value'])->year)
                ->update([
                    'remaining_days' => $spesificAttributes['remaining_days'],
                ]);
        }
    }

    private function filterArrayByFullname(array $mainArray, string $filteredKey, string $arrayKey, bool $isNested = false)
    {
        return array_filter($mainArray, function ($item) use ($arrayKey, $filteredKey, $isNested) {
            if ($isNested) {
                $key = '$' . $arrayKey;
                return isset($item[$key]['value']) && $item[$key]['value'] === $filteredKey;
            }
            return isset($item[$arrayKey]) && $item[$arrayKey] == $filteredKey;
        });
    }

    private function removeVacationPersonnels($orderLog): void
    {
        $orderLog->load('vacations');

        $orderLog->vacations()->each(function ($vacation) {
            $vacation->delete();
        });
    }

    private function removeBusinessTripsPersonnels($orderLog): void
    {
        $orderLog->load('businessTrips');

        $orderLog->businessTrips()->each(function ($businessTrip) {
            $businessTrip->delete();
        });
    }
}
