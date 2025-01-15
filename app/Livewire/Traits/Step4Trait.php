<?php

namespace App\Livewire\Traits;

use App\Services\CalculateSeniorityService;

trait Step4Trait
{
    public $labor_activities = [];

    public $labor_activities_list = [];

    public $ranks = [];

    public $rank_list = [];

    public $isAddedRank;

    public $rankId;

    public $rankName;

    public $searchRank;

    public $isSpecialService;

    public $calculatedData = [];

    public function addLaborActivity(): void
    {
        $this->validate($this->exceptArray('ranks'));
        $time = array_key_exists('time', $this->labor_activities) ? $this->labor_activities['time'] : '12:00';
        if ($this->isSpecialService) {
            $this->labor_activities['is_special_service'] = $this->isSpecialService ? 1 : 0;
            $this->labor_activities['order_date'] .= " {$time}";
            unset($this->labor_activities['time']);
        }
        else
        {
            $this->labor_activities['is_special_service'] ??= false;
        }
        $this->labor_activities['is_current'] ??= false;
        $this->labor_activities_list[] = $this->labor_activities;
        $this->labor_activities = [];
        $this->calculateSeniority();
    }

    public function addRank(): void
    {
        $this->isAddedRank = true;
        $this->validate($this->exceptArray('labor_activities'));
        $this->rank_list[] = $this->ranks;
        $this->rankName = '---';
        $this->reset('rankId');
        $this->ranks = [];
        $this->isAddedRank = false;
    }

    public function forceDeleteLaborActivity($key): void
    {
        unset($this->labor_activities_list[$key]);
        $this->calculateSeniority();
    }

    public function forceDeleteRank($key): void
    {
        unset($this->rank_list[$key]);
    }

    public function mountStep4Trait(): void
    {
        $this->isAddedRank = false;
        $this->rankName = '---';
        ! empty($this->personnelModel) && $this->fillStep4();
        $this->isSpecialService = false;
        $this->calculateSeniority();
    }

    private function calculateSeniority(): void
    {
        $calculateService = resolve(CalculateSeniorityService::class);
        $this->calculatedData = $calculateService->calculateMulti($this->labor_activities_list);
    }

    protected function fillStep4(): void
    {
        $updateLaborActivity = $this->personnelModelData->laborActivities;
        if (! empty($updateLaborActivity)) {
            foreach ($updateLaborActivity as $key => $uptLabor) {
                $this->labor_activities_list[] = $this->mapAttributes(
                    attributes: [
                        'company_name', 'position', 'coefficient', 'join_date', 'leave_date',
                        'is_special_service', 'order_given_by', 'order_no', 'order_date', 'is_current',
                    ],
                    getFrom: $uptLabor->toArray(),
                    booleanColumns: ['is_special_service']
                );
            }
        }
        $updateRanks = $this->personnelModelData->ranks->load(['rank', 'rankReason'])->toArray();

        if (! empty($updateRanks)) {
            foreach ($updateRanks as $key => $uptRank) {
                $this->rank_list[] = [
                    'name' => $uptRank['name'],
                    'given_date' => $uptRank['given_date'],
                    'order_no' => $uptRank['order_no'],
                    'order_given_by' => $uptRank['order_given_by'],
                    'order_date' => $uptRank['order_date'],
                ];

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'rank',
                    field: 'rank_id',
                    key: $key,
                    fillTo: 'rank_list',
                    getFrom: $uptRank,
                    titleField: 'name',
                    hasLocale: true
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'rank_reason',
                    field: 'rank_reason_id',
                    key: $key,
                    fillTo: 'rank_list',
                    getFrom: $uptRank,
                    titleField: 'name'
                );
            }
        }
    }
}
