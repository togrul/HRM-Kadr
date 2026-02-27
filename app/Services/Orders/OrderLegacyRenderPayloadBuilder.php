<?php

namespace App\Services\Orders;

use App\Helpers\UsefulHelpers;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Structure;
use App\Services\GenerateWordReplaceContent;
use App\Services\WordSuffixService;
use Carbon\Carbon;

class OrderLegacyRenderPayloadBuilder
{
    public function __construct(private readonly WordSuffixService $suffixService)
    {
    }

    public function build(OrderLog $orderLog): array
    {
        $orderLog->loadMissing(['order', 'components', 'attributes.component']);

        $givenDate = Carbon::parse($orderLog->given_date);
        $bladeType = (string) ($orderLog->order?->blade ?? '');

        $scalarValues = [
            'day' => $givenDate->format('d'),
            'month' => $givenDate->locale('AZ')->monthName,
            'year' => $givenDate->format('Y'),
            'rank_director' => $orderLog->given_by_rank,
            'name_director' => $orderLog->given_by,
        ];

        if ($bladeType === Order::BLADE_BUSINESS_TRIP && is_array($orderLog->description)) {
            $endDate = Carbon::parse($orderLog->description['end_date'] ?? null);
            $startDate = Carbon::parse($orderLog->description['start_date'] ?? null);
            $startDateFormat = $startDate->format('d');

            if ($startDate->format('m') !== $endDate->format('m')) {
                $startDateFormat .= " {$startDate->locale('AZ')->monthName}";
            }

            if ($startDate->format('Y') !== $endDate->format('Y')) {
                $startDateFormat .= " {$startDate->format('Y')}";
            }

            $scalarValues['location'] = $orderLog->description['location'] ?? '';
            $scalarValues['day_start'] = $startDateFormat;
            $scalarValues['day_end'] = $endDate->format('d');
            $scalarValues['month_trip'] = $endDate->locale('AZ')->monthName;
            $scalarValues['year_trip'] = $endDate->format('Y')
                . $this->suffixService->getNumberSuffix((int) $endDate->format('Y'));
        }

        $attributesByComponent = $orderLog->attributes;
        if ($bladeType === Order::BLADE_BUSINESS_TRIP) {
            $componentTexts = $attributesByComponent->groupBy(function ($attribute) {
                $rowNumber = $attribute->attributes['row']['value'] ?? null;
                $transportation = $attribute->attributes['$transportation']['value'] ?? null;
                return "{$rowNumber}_{$transportation}";
            });
        } else {
            $componentTexts = $attributesByComponent->groupBy('row_number');
        }

        $componentTexts = $componentTexts->map(function ($group) {
            $component = $group->first()->component;

            return [
                'title' => $component?->title,
                'content' => $group->pluck('component.content')->toArray(),
            ];
        })->toArray();

        $replacementData = [];
        $globalIndex = 0;

        foreach ($orderLog->components as $componentIndex => $component) {
            $attributeList = $orderLog->attributes
                ->where('component_id', $component->id)
                ->where('row_number', $componentIndex)
                ->pluck('attributes')
                ->toArray();

            foreach ($attributeList as $attribute) {
                if ($bladeType === Order::BLADE_BUSINESS_TRIP) {
                    $key = "{$attribute['row']['value']}_{$attribute['$transportation']['value']}";
                    $replacementData[$key][] = UsefulHelpers::modifyArrayToKeyValuePair($attribute);
                    $lastIndex = array_key_last($replacementData[$key]);
                    $replacementData[$key][$lastIndex]['order'] = $bladeType;
                } else {
                    $replacementData[] = UsefulHelpers::modifyArrayToKeyValuePair($attribute);
                    $key = $globalIndex++;
                    $replacementData[$key]['order'] = $bladeType;
                }

                switch ($bladeType) {
                    case Order::BLADE_DEFAULT:
                        $replacementData[$key]['$year'] .= $this->suffixService->getNumberSuffix((int) $replacementData[$key]['$year']);
                        $replacementData[$key]['$surname'] = $this->suffixService->getSurnameSuffix($replacementData[$key]['$surname']);
                        $replacementData[$key]['$structure_main'] = $this->suffixService->getStructureSuffix($replacementData[$key]['$structure_main']);
                        $replacementData[$key]['$fullname'] = $this->asPlainText($replacementData[$key]['$fullname']);
                        break;
                    case Order::BLADE_VACATION:
                        $replacementData[$key]['$start_date'] .= $this->suffixService->getNumberSuffix(Carbon::parse($replacementData[$key]['$start_date'])->year);
                        $replacementData[$key]['$end_date'] .= $this->suffixService->getNumberSuffix(Carbon::parse($replacementData[$key]['$end_date'])->year);
                        $replacementData[$key]['$structure'] = $this->getFullStructureNameWithSuffixes($replacementData[$key]['$structure']);
                        $replacementData[$key]['$position'] = $this->suffixService->getMultiSuffix($replacementData[$key]['$position'], multi: false);
                        break;
                    case Order::BLADE_BUSINESS_TRIP:
                        $tripStart = Carbon::parse($replacementData[$key][$lastIndex]['$start_date']);
                        $replacementData[$key][$lastIndex]['$trip_start_day'] = $tripStart->format('d');
                        $replacementData[$key][$lastIndex]['$trip_start_month'] = $tripStart->locale('AZ')->monthName;
                        $replacementData[$key][$lastIndex]['$trip_start_year'] = $tripStart->year
                            . $this->suffixService->getNumberSuffix($tripStart->year)
                            . ' ' . __('year');
                        $replacementData[$key][$lastIndex]['$trip_location'] = $replacementData[$key][$lastIndex]['$location'];
                        $replacementData[$key][$lastIndex]['$return_day'] = $this->suffixService->getMonthDaySuffix($replacementData[$key][$lastIndex]['$return_day']);
                        $replacementData[$key][$lastIndex]['$meeting_hour'] = $this->suffixService->getTimeSuffix($replacementData[$key][$lastIndex]['$meeting_hour']);
                        break;
                }
            }
        }

        $rows = [];
        $index = 0;
        foreach ($componentTexts as $key => &$text) {
            ['content' => $content, 'title' => $title] = (new GenerateWordReplaceContent($bladeType, $replacementData))
                ->handle($key, $text, $index);
            $index++;
            $mergedText = ! empty($title) ? $this->asPlainText($title) . PHP_EOL . $content : $content;

            $rows[] = [
                'content_text' => match ($bladeType) {
                    Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP => $mergedText,
                    Order::BLADE_DEFAULT => ((int) $key + 1) . '. ' . $mergedText,
                    default => $mergedText,
                },
            ];
        }

        return [
            'scalar_values' => $scalarValues,
            'rows' => $rows,
            'mode' => 'legacy',
            'template_version_id' => null,
        ];
    }

    private function getFullStructureNameWithSuffixes(?string $name): string
    {
        if (! $name) {
            return '';
        }

        $structureModel = Structure::where('name', $name)->first();
        if (! $structureModel) {
            return $name;
        }

        $structureFullName = $structureModel->getAllParentName(isCoded: true);

        return collect($structureFullName)->map(
            fn ($structure) => $this->suffixService->getStructureSuffix($structure, mainStructure: true) . ' '
        )->implode('');
    }

    private function asPlainText(?string $word): string
    {
        return (string) $word;
    }
}
