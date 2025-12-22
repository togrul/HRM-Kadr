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

        $this->rank = ! empty($rank)
            ? array_replace($this->defaultRank(), $rank)
            : $this->defaultRank();

        $this->rankList = collect($rankList)
            ->map(fn ($entry) => array_replace($this->defaultRank(), $entry ?? []))
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

                $payload['position'] = $activity->position_label;
                $payload['is_special_service'] = (bool) ($payload['is_special_service'] ?? false);
                $payload['is_current'] = (bool) ($payload['is_current'] ?? false);
                $payload['time'] = '12:00';

                return $payload;
            })
            ->values()
            ->all();

        $this->rankList = $personnel->ranks
            ->map(function ($rank) {
                return array_replace(
                    $this->defaultRank(),
                    Arr::only($rank->toArray(), array_keys($this->defaultRank()))
                );
            })
            ->values()
            ->all();
    }

    public function addLaborActivityEntry(bool $isSpecialService = false): void
    {
        $entry = $this->laborActivity;
        $entry['is_special_service'] = $isSpecialService ? 1 : 0;
        $entry['is_current'] = (bool) ($entry['is_current'] ?? false);

        if ($isSpecialService) {
            $time = trim((string) ($entry['time'] ?? '12:00'));
            $orderDate = trim((string) ($entry['order_date'] ?? ''));
            $entry['order_date'] = trim("{$orderDate} {$time}");
        } else {
            $entry['order_given_by'] = null;
            $entry['order_no'] = null;
            $entry['order_date'] = null;
        }

        unset($entry['time']);

        $this->laborActivityList[] = $entry;
        $this->resetLaborActivity();
    }

    public function removeLaborActivityEntry(int $index): void
    {
        if (! array_key_exists($index, $this->laborActivityList)) {
            return;
        }

        unset($this->laborActivityList[$index]);
        $this->laborActivityList = array_values($this->laborActivityList);
    }

    public function addRankEntry(): void
    {
        $this->rankList[] = $this->rank;
        $this->resetRank();
    }

    public function removeRankEntry(int $index): void
    {
        if (! array_key_exists($index, $this->rankList)) {
            return;
        }

        unset($this->rankList[$index]);
        $this->rankList = array_values($this->rankList);
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

    public function laborActivitiesForPersistence(): array
    {
        return collect($this->laborActivityList ?? [])
            ->map(fn ($activity) => Arr::except($activity, ['time']))
            ->all();
    }

    public function ranksForPersistence(): array
    {
        return $this->rankList ?? [];
    }
}
