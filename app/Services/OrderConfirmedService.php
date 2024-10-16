<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Helpers\UsefulHelpers;
use App\Livewire\Outside\BusinessTrips;
use App\Models\Candidate;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\StaffSchedule;
use Carbon\Carbon;

class OrderConfirmedService
{
    public function __construct(public OrderLog $orderLog) {}

    public function handle($personnelIds = [], $action = 'create'): void
    {
        $this->orderLog->load('order');
        $statusId = $this->orderLog->status_id;
        $order = $this->orderLog->order;

        if ($statusId == OrderStatusEnum::APPROVED->value) {
            $this->handleApprovedOrder($personnelIds, $action, $order);
        } elseif ($statusId == OrderStatusEnum::CANCELLED->value) {
            $this->handleCancelledOrder($order);
        }
    }

    private function handleApprovedOrder(array $personnelIds, string $action, $order): void
    {
        // eger ise girme emridirse ve statusu tesdiqlenmisdirse
        if ($this->orderLog->order_id == Order::IG_EMR) {
            $this->approveEmploymentOrder($personnelIds, $this->orderLog, $action);
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

    private function approveEmploymentOrder($personnelIds, $orderLog, $action): void
    {
        // Change status to accepted
        Candidate::whereIn('id', $personnelIds)->update(['status_id' => 70]);

        $convertedIds = array_map(fn ($x) => "NMZD{$x}", $personnelIds);

        $personnelModel = $action == 'create'
            ? $orderLog->personnels
            : Personnel::whereIn('tabel_no', $convertedIds)
                ->where('is_pending', true)
                ->with(['ranks', 'structure', 'position', 'laborActivities'])
                ->get();

        foreach ($personnelModel as $_personnel) {
            $this->updatePersonnelEmployment($_personnel, $orderLog);
        }
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

        $_personnel->update([
            'join_work_date' => $date,
            'is_pending' => false,
        ]);

        $_personnel->ranks()->create([
            'rank_id' => $_attr['$rank']['id'] ?? 10,
            'name' => 'İşə qəbul',
            'given_date' => $date,
        ]);

        $this->updateStaffSchedule($_personnel);

        $this->endCurrentLaborActivity($_personnel, $date);

        $_personnel->laborActivities()->create($this->getNewLaborActivityData($_personnel, $orderLog, $date));
    }

    private function updateStaffSchedule($_personnel): void
    {
        $staff = StaffSchedule::where('structure_id', $_personnel->structure_id)
            ->where('position_id', $_personnel->position_id)
            ->first();

        $staff->update([
            'filled' => $staff->filled + 1,
            'vacant' => $staff->vacant > 0 ? $staff->vacant - 1 : 0,
        ]);
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
        $orderLog->load('personnels');
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
            'start_date' => Carbon::parse($arr[$key]['$start_date']['value'])->format('Y-m-d'),
            'end_date' => Carbon::parse($arr[$key]['$end_date']['value'])->format('Y-m-d'),
            'order_no' => $orderLog->order_no,
            'order_date' => $orderLog->given_date,
        ];

        $relationshipMethod = '';

        $filteredAttributes = array_filter($orderAttributes, function ($item) use ($_person) {
            return isset($item['$fullname']['value']) && $item['$fullname']['value'] == $_person->fullname;
        });

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

                $relationshipMethod = 'vacations';
                break;
            case Order::BLADE_BUSINESS_TRIP:
                $spesificAttributes['location'] = $arr[$key]['$location']['value'];
                $spesificAttributes['description'] = $arr[$key]['$description'] ?? '';

                $relationshipMethod = 'businessTrips';
                break;
        }

        $_person->{$relationshipMethod}()->withTrashed()->updateOrCreate(
            $commonAttributes,
            $spesificAttributes
        );
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
