<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class LaborActivityForm extends Form
{
    public array $laborActivity = [];

    public array $laborActivityList = [];

    public array $rank = [];

    public array $rankList = [];

    public function resetForm(): void
    {
        $this->laborActivity = $this->defaultLaborActivity();
        $this->laborActivityList = [];
        $this->rank = $this->defaultRank();
        $this->rankList = [];
    }

    public function resetLaborActivity(): void
    {
        $this->laborActivity = $this->defaultLaborActivity();
    }

    public function resetRank(): void
    {
        $this->rank = $this->defaultRank();
    }

    public function fillFromArrays(
        array $laborActivity,
        array $laborActivityList,
        array $rank,
        array $rankList
    ): void {
        $this->laborActivity = ! empty($laborActivity)
            ? array_replace($this->defaultLaborActivity(), $laborActivity)
            : $this->defaultLaborActivity();

        $this->laborActivityList = $laborActivityList;

        $this->rank = $this->prepareRankPayload($rank);

        $this->rankList = collect($rankList)
            ->map(fn ($entry) => $this->prepareRankPayload($entry))
            ->all();
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing(['laborActivities', 'ranks.rank', 'ranks.rankReason']);

        $this->laborActivityList = $personnel->laborActivities
            ->map(function ($activity) {
                $payload = array_replace(
                    $this->defaultLaborActivity(),
                    Arr::only($activity->toArray(), array_keys($this->defaultLaborActivity()))
                );

                $payload['is_special_service'] = (bool) ($payload['is_special_service'] ?? false);
                $payload['is_current'] = (bool) ($payload['is_current'] ?? false);
                $payload['time'] = '12:00';

                return $payload;
            })
            ->values()
            ->all();

        $this->rankList = $personnel->ranks
            ->map(function ($rank) {
                return $this->prepareRankPayload(
                    Arr::only($rank->toArray(), array_keys($this->defaultRank()))
                );
            })
            ->values()
            ->all();
    }

    protected function defaultLaborActivity(): array
    {
        return [
            'company_name' => null,
            'position' => null,
            'coefficient' => null,
            'join_date' => null,
            'leave_date' => null,
            'order_given_by' => null,
            'order_no' => null,
            'order_date' => null,
            'time' => '12:00',
            'is_current' => false,
            'is_special_service' => false,
        ];
    }

    protected function defaultRank(): array
    {
        return [
            'rank_id' => null,
            'rank_reason_id' => null,
            'name' => null,
            'given_date' => null,
            'order_no' => null,
            'order_given_by' => null,
            'order_date' => null,
        ];
    }

    protected function prepareRankPayload(array $rank): array
    {
        $payload = array_replace($this->defaultRank(), $rank);

        if (isset($payload['rank_id']) && is_array($payload['rank_id'])) {
            $payload['rank_id'] = $payload['rank_id']['id'] ?? null;
        }

        if (isset($payload['rank_reason_id']) && is_array($payload['rank_reason_id'])) {
            $payload['rank_reason_id'] = $payload['rank_reason_id']['id'] ?? null;
        }

        return $payload;
    }
}
