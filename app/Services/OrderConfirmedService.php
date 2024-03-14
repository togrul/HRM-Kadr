<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\OrderLog;
use Carbon\Carbon;

class OrderConfirmedService
{
    public function handle(OrderLog $orderLog,$personnelIds = [])
    {
        if($orderLog->order_id == 1010)
        {
            if($orderLog->status_id == OrderStatusEnum::APPROVED->value)
            {
                //qebul olundu statusuna cevirmek
                Candidate::find($personnelIds)->update([
                    'status_id' => 70
                ]);

                //personnel cedvelinde ise girme tarixin duzeltmek
                foreach($orderLog->personnels as $_personnel)
                {
                    $_attr = $orderLog->personnels()
                                ->join('order_log_component_attributes', 'order_log_personnels.component_id', '=', 'order_log_component_attributes.component_id')
                                ->where('order_log_personnels.tabel_no', $_personnel->tabel_no)
                                ->select('order_log_component_attributes.attribute_key', 'order_log_component_attributes.attribute_value')
                                ->pluck('attribute_value', 'attribute_key')
                                ->toArray();

                    $month = Carbon::parse("1" . $_attr['$month'])->locale('AZ')->month;
                    $date = "{$_attr['$year']}-{$month}-{$_attr['$day']}";

                    $_personnel->update([
                        'join_work_date' => Carbon::parse($date)->format('Y-m-d'),
                    ]);
                }
            }
        }
    }
}
