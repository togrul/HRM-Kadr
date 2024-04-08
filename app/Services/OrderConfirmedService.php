<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Helpers\UsefulHelpers;
use App\Models\Candidate;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\StaffSchedule;
use Carbon\Carbon;

class OrderConfirmedService
{
    public function handle(OrderLog $orderLog,$personnelIds = [],$action = 'create')
    {
        //eger ise girme emridirse ve statusu tesdiqlenmisdirse
        if($orderLog->order_id == 1010 && $orderLog->status_id == OrderStatusEnum::APPROVED->value)
        {
                //qebul olundu statusuna cevirmek
                Candidate::whereIn('id',$personnelIds)->update([
                    'status_id' => 70
                ]);

                $convertedIds = array_map(function ($x){
                    return "NMZD{$x}";
                },$personnelIds);

                $personnelModel = $action == 'create'
                    ? $orderLog->personnels
                    : Personnel::whereIn('tabel_no',$convertedIds)
                        ->where('is_pending',true)
                        ->get();

                //personnel cedvelinde ise girme tarixin duzeltmek
                foreach($personnelModel as $key => $_personnel)
                {
                    $_attr = $orderLog->personnels()
                                ->join('order_log_component_attributes', 'order_log_personnels.component_id', '=', 'order_log_component_attributes.component_id')
                                ->where('order_log_personnels.tabel_no', $_personnel->tabel_no)
                                ->select('order_log_component_attributes.attribute_key', 'order_log_component_attributes.attribute_value')
                                ->pluck('attribute_value', 'attribute_key')
                                ->toArray();


                    $month = UsefulHelpers::convertToMonthNumber($_attr['$month'],config('app.locale'));

                    $date = "{$_attr['$year']}-{$month}-{$_attr['$day']}";

                    $_personnel->update([
                        'join_work_date' => Carbon::parse($date)->format('Y-m-d'),
                        'is_pending' => false
                    ]);

                    $_personnel->ranks()->create([
                        'rank_id' => $orderLog->components[$key]['rank_id'],
                        'name' => 'İşə qəbul',
                        'given_date' => $orderLog->given_date
                    ]);

                    $staff = StaffSchedule::where('structure_id',$_personnel->structure_id)
                        ->where('position_id',$_personnel->position_id)
                        ->first();

                    $staff->update([
                        'filled' => $staff->filled + 1,
                        'vacant' => $staff->vacant > 0 ? $staff->vacant - 1 : 0
                    ]);
                }
        }
    }
}
