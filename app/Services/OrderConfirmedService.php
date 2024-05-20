<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Helpers\UsefulHelpers;
use App\Models\Candidate;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\StaffSchedule;
use Illuminate\Support\Facades\DB;

class OrderConfirmedService
{
    public function handle(OrderLog $orderLog,$personnelIds = [],$action = 'create')
    {
        //eger ise girme emridirse ve statusu tesdiqlenmisdirse
        if($orderLog->order_id == 1010 && $orderLog->status_id == OrderStatusEnum::APPROVED->value)
        {
            //personnel cedvelinde ise girme tarixin duzeltmek
                DB::transaction(function() use($personnelIds,$orderLog,$action){
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
                            ->with(['ranks','structure','position','laborActivities'])
                            ->get();

                    foreach($personnelModel as $key => $_personnel)
                    {
                        $component_id = $orderLog->personnels()
                            ->where('order_log_personnels.tabel_no',$_personnel->tabel_no)
                            ->value('component_id');

                        $_attr = $orderLog->attributes()
                            ->where('component_id', $component_id)
                            ->value('attributes');

                        $month = UsefulHelpers::convertToMonthNumber($_attr['$month']['value'],config('app.locale'));

                        $date = "{$_attr['$year']['value']}-{$month}-{$_attr['$day']['value']}";

                        $_personnel->update([
                            'join_work_date' => $date,
                            'is_pending' => false
                        ]);

                        $_personnel->ranks()->create([
                            'rank_id' => $_attr['$rank']['id'] ?? 10,
                            'name' => 'İşə qəbul',
                            'given_date' => $date
                        ]);

                        $staff = StaffSchedule::where('structure_id',$_personnel->structure_id)
                            ->where('position_id',$_personnel->position_id)
                            ->first();

                        $staff->update([
                            'filled' => $staff->filled + 1,
                            'vacant' => $staff->vacant > 0 ? $staff->vacant - 1 : 0
                        ]);

                        $has_active_work =  $_personnel->laborActivities()
                                            ->where('is_current',true)
                                            ->whereNull('leave_date')
                                            ->first();

                        if($has_active_work)
                        {
                            $has_active_work->update([
                                'leave_date' => $date,
                                'is_current' => false
                            ]);
                        }

                        $_personnel->laborActivities()->create([
                            'company_name' => config('app.company'),
                            'position' => $_personnel->position->name,
                            'coefficient' => $_personnel->structure->coefficient,
                            'join_date' => $date,
                            'is_special_service' => true,
                            'order_given_by' => "{$orderLog->given_by_rank} {$orderLog->given_by}",
                            'order_no' => $orderLog->order_no,
                            'order_date' => $orderLog->given_date,
                            'is_current' => true
                        ]);
                    }
                });


        }
    }
}
