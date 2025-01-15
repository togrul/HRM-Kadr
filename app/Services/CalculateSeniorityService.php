<?php

namespace App\Services;

use Carbon\Carbon;

class CalculateSeniorityService
{
    public function calculate(
        $join_date,
        $leave_date,
        $coefficient
    ): array {
        $diffInMonths = Carbon::parse($join_date)->diffInMonths(Carbon::parse($leave_date));
        $duration = $coefficient ? $coefficient * $diffInMonths : $diffInMonths;
        $yearAndMonth = $this->calculateYearAndMonth($diffInMonths);

        return [
            'diff' => $diffInMonths,
            'duration' => $duration,
            'year' => $yearAndMonth['year'],
            'month' => $yearAndMonth['month'],
        ];
    }

    public function calculateEducation(array $education): array
    {
        $result = $this->calculate(
            $education['admission_year'],
            $education['graduated_year'] ?: Carbon::now()->format('Y-m-d'),
            $education['coefficient']
        );
        $calculateCoefficientYearMonth = $this->calculateYearAndMonth($result['duration']);
        $result['year_coefficient'] = $calculateCoefficientYearMonth['year'];
        $result['month_coefficient'] = $calculateCoefficientYearMonth['month'];

        return $result;
    }

    public function calculateMultiEducation($edu_list): array
    {
        $mappedData = collect($edu_list)->map(function ($item) {
            $graduatedYear = $item['graduated_year'] ?? Carbon::now()->format('Y-m-d');
            $duration = $this->calculate($item['admission_year'], $graduatedYear, $item['coefficient']);

            $old = $current = $extraSeniority = [];
            if (! empty($item['graduated_year'])) {
                $old = $this->calculate($item['admission_year'], $item['graduated_year'], $item['coefficient']);
            } else {
                $current = $this->calculate($item['admission_year'], Carbon::now()->format('Y-m-d'), $item['coefficient']);
            }

            if ($item['calculate_as_seniority'] ?? false) {
                $extraSeniority = $old + ($current ?? []);
                $coefficient = $this->calculateYearAndMonth($duration['duration']);
            } else {
                $coefficient = [];
            }

            return [
                'old' => $old,
                'current' => $current,
                'duration' => $duration,
                'extra_seniority' => $extraSeniority,
                'coefficient' => $coefficient,
            ];
        })->toArray();

        return $this->aggregateMultiEducationResults($mappedData);
    }

    private function aggregateMultiEducationResults(array $mappedData): array
    {
        $extraSeniority = array_sum(array_column(array_column($mappedData, 'extra_seniority'), 'duration'));
        $totalDuration = array_sum(array_column(array_column($mappedData, 'duration'), 'diff'));

        return [
            'data' => $mappedData,
            'extra_seniority' => $extraSeniority,
            'extra_seniority_full' => $this->calculateYearAndMonth($extraSeniority),
            'total_duration' => $totalDuration,
            'total_duration_diff' => $this->calculateYearAndMonth($totalDuration),
        ];
    }

    public function calculateMulti(array $list, string $currentWorkType = 'military'): array
    {
        $mappedData = collect($list)->map(function ($item) use ($currentWorkType) {
            $leaveDate = $item['leave_date'] ?? Carbon::now();
            $coefficient = $item['coefficient'] ?? 1;
            $duration = $this->calculate($item['join_date'], $leaveDate, $coefficient);

            $old = $oldMilitary = $current = [];
            if (!$item['is_current']) {
                $key = $item['is_special_service'] ? 'oldMilitary' : 'old';
                ${$key} = $this->calculate($item['join_date'], $leaveDate, $coefficient);
            } else {
                $current = $this->calculate($item['join_date'], $leaveDate, $coefficient);
            }

            return [
                'old' => $old,
                'old_military' => $oldMilitary,
                'current' => $current,
                'duration' => $duration,
            ];
        })->toArray();

        return $this->aggregateMultiResults($mappedData, $currentWorkType);
    }

    private function aggregateMultiResults(array $mappedData, string $currentWorkType): array
    {
        $oldMonths = $this->getColumnListFromArray($mappedData, 'old', 'duration');
        $oldMilitaryMonths = $this->getColumnListFromArray($mappedData, 'old_military', 'duration');
        $currentMonths = $this->getColumnListFromArray($mappedData, 'current', 'duration');
        $currentDiffs = $this->getColumnListFromArray($mappedData, 'current', 'diff');
        $fullSum = $this->getColumnListFromArray($mappedData, 'duration', 'duration');

        $sumOld = array_sum($oldMonths);
        $sumMilitaryOld = array_sum($oldMilitaryMonths);
        $sumCurrent = array_sum($currentMonths);
        $sumCurrentDiff = array_sum($currentDiffs);
        $sumFull = array_sum($fullSum);

        $sumTotal = $sumOld + ($currentWorkType === 'military' ? 0 : $sumCurrent);
        $sumTotalMilitary = $sumMilitaryOld + ($currentWorkType === 'military' ? $sumCurrent : 0);

        return [
            'data' => $mappedData,
            'sum_month_old' => $sumOld,
            'sum_month_military_old' => $sumMilitaryOld,
            'sum_month_current' => $sumCurrent,
            'sum_month_current_diff' => $sumCurrentDiff,
            'sum_month' => $sumFull,
            'sum_total' => $sumTotal,
            'sum_total_military' => $sumTotalMilitary,
            'sum_old' => $this->calculateYearAndMonth($sumOld),
            'sum_old_military' => $this->calculateYearAndMonth($sumMilitaryOld),
            'sum_current' => $this->calculateYearAndMonth($sumCurrent),
            'sum_current_diff' => $this->calculateYearAndMonth($sumCurrentDiff),
            'sum_full' => $this->calculateYearAndMonth($sumFull),
            'sum_total_full' => $this->calculateYearAndMonth($sumTotal),
            'sum_total_military_full' => $this->calculateYearAndMonth($sumTotalMilitary),
        ];
    }

    private function getColumnListFromArray(array $data, $parentColumnKey, $subColumnKey): array
    {
        return array_column(array_column($data, $parentColumnKey), $subColumnKey);
    }

    public function calculateYearAndMonth($total_month): array
    {
        $calculate['year'] = floor($total_month / 12);
        $calculate['month'] = $total_month % 12;

        return $calculate;
    }
}
