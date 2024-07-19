<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Helpers\UsefulHelpers;
use App\Models\Candidate;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\StaffSchedule;
use Carbon\Carbon;

class OrderConfirmedService
{
    public function __construct(public OrderLog $orderLog){}
    public function handle($personnelIds = [],$action = 'create')
    {
        $this->orderLog->load('order');
        if($this->orderLog->status_id == OrderStatusEnum::APPROVED->value)
        {
            // eger ise girme emridirse ve statusu tesdiqlenmisdirse
            if($this->orderLog->order_id == Order::IG_EMR)
            {
                // personnel cedvelinde ise girme tarixin duzeltmek
                $this->approveEmploymentOrder($personnelIds, $this->orderLog, $action);
            }
            if($this->orderLog->order->blade == Order::BLADE_VACATION)
            {
                $this->processVacationOrder($this->orderLog);
            }
        }
        elseif($this->orderLog->status_id == OrderStatusEnum::CANCELLED->value)
        {
            if($this->orderLog->order->blade == Order::BLADE_VACATION)
            {
                // remove all personnels from vacation table
                $this->removeVacationPersonnels($this->orderLog);
            }
        }
    }

    private function approveEmploymentOrder($personnelIds, $orderLog, $action)
    {
        // Change status to accepted
        Candidate::whereIn('id', $personnelIds)->update(['status_id' => 70]);

        $convertedIds = array_map(fn($x) => "NMZD{$x}", $personnelIds);

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


    private function updatePersonnelEmployment($_personnel, $orderLog)
    {
        $component_id = $orderLog->personnels()
            ->where('order_log_personnels.tabel_no', $_personnel->tabel_no)
            ->value('component_id');

        $_attr = $orderLog->attributes()
            ->where('component_id', $component_id)
            ->value('attributes');

        $month = UsefulHelpers::convertToMonthNumber($_attr['$month']['value'],config('app.locale'));

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

    private function updateStaffSchedule($_personnel)
    {
        $staff = StaffSchedule::where('structure_id', $_personnel->structure_id)
            ->where('position_id', $_personnel->position_id)
            ->first();

        $staff->update([
            'filled' => $staff->filled + 1,
            'vacant' => $staff->vacant > 0 ? $staff->vacant - 1 : 0,
        ]);
    }

    private function endCurrentLaborActivity($_personnel, $date)
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

    private function getNewLaborActivityData($_personnel, $orderLog, $date)
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

    private function processVacationOrder($orderLog)
    {
        $orderLog->load('personnels');
        $orderAttributes = $orderLog->attributes->pluck('attributes')->toArray();

        foreach ($orderLog->personnels as $key => $_person) {
            $this->updateOrCreateVacation($_person, $orderAttributes, $key, $orderLog);
        }
    }

    private function updateOrCreateVacation($_person, $orderAttributes, $key, $orderLog)
    {
        $arr = UsefulHelpers::searchInsideMultiDimensionalArray($orderAttributes, $_person->fullname, '$fullname', 'value');

        $_person->vacations()->withTrashed()->updateOrCreate(
            [
                'start_date' => Carbon::parse($arr[$key]['$start_date']['value'])->format('Y-m-d'),
                'end_date' => Carbon::parse($arr[$key]['$end_date']['value'])->format('Y-m-d'),
                'order_no' => $orderLog->order_no,
                'order_date' => $orderLog->given_date,
            ],
            [
                'vacation_places' => $arr[$key]['$location']['value'],
                'duration' => $arr[$key]['$days']['value'],
                'start_date' => Carbon::parse($arr[$key]['$start_date']['value'])->format('Y-m-d'),
                'end_date' => Carbon::parse($arr[$key]['$end_date']['value'])->format('Y-m-d'),
                'return_work_date' => Carbon::parse($arr[$key]['$end_date']['value'])->addDay()->format('Y-m-d'),
                'order_given_by' => "{$orderLog->given_by_rank} {$orderLog->given_by}",
                'order_no' => $orderLog->order_no,
                'order_date' => Carbon::parse($orderLog->given_date)->format('Y-m-d'),
                'deleted_by' => null,
                'deleted_at' => null,
            ]
        );
    }

    private function removeVacationPersonnels($orderLog)
    {
        $orderLog->load('vacations');

        $orderLog->vacations()->each(function ($vacation) {
            $vacation->delete();
        });
    }
}
