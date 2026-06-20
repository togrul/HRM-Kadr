<?php

namespace App\Modules\Reports\Application\Services;

class StandardReportCatalogService
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function all(): array
    {
        return [
            [
                'key' => 'headcount',
                'label' => __('reports::dashboard.standard.types.headcount'),
                'description' => __('reports::dashboard.standard.descriptions.headcount'),
            ],
            [
                'key' => 'demographics',
                'label' => __('reports::dashboard.standard.types.demographics'),
                'description' => __('reports::dashboard.standard.descriptions.demographics'),
            ],
            [
                'key' => 'movements',
                'label' => __('reports::dashboard.standard.types.movements'),
                'description' => __('reports::dashboard.standard.descriptions.movements'),
            ],
            [
                'key' => 'attendance',
                'label' => __('reports::dashboard.standard.types.attendance'),
                'description' => __('reports::dashboard.standard.descriptions.attendance'),
            ],
            [
                'key' => 'training',
                'label' => __('reports::dashboard.standard.types.training'),
                'description' => __('reports::dashboard.standard.descriptions.training'),
            ],
            [
                'key' => 'performance',
                'label' => __('reports::dashboard.standard.types.performance'),
                'description' => __('reports::dashboard.standard.descriptions.performance'),
            ],
        ];
    }
}
