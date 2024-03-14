<?php

namespace App\Services;

class GenerateDynamicFieldsService
{
    public function handle()
    {
        return [
            '$fullname' => [
                'field' => 'personnel_id',
                'title' => __('Select personnel'),
                'model' => '_personnels',
                'selectedName' => 'personnel',
                'searchField' => 'searchPersonnel'
            ],
            '$rank' => [
                'field' => 'rank_id',
                'title' => __('Select rank'),
                'model' => '_ranks',
                'selectedName' => 'rank',
            ],
            '$day' => [
                'field' => 'day',
                'title' => __('Day')
            ],
            '$month' => [
                'field' => 'month',
                'title' => __('Month')
            ],
            '$year' => [
                'field' => 'year',
                'title' => __('Year')
            ],
            '$name' => [
                'field' => 'name',
                'title' => __('Name')
            ],
            '$surname' => [
                'field' => 'surname',
                'title' => __('Surname')
            ],
            '$structure_main' => [
                'field' => 'structure_main_id',
                'title' => __('Select main structure'),
                'model' => '_main_structures',
                'selectedName' => 'mainStructure',
            ],
            '$structure' => [
                'field' => 'structure_id',
                'title' => __('Select structure'),
                'model' => '_structures',
                'selectedName' => 'structure',
                'searchField' => 'searchStructure'
            ],
            '$position' => [
                'field' => 'position_id',
                'title' => __('Select position'),
                'model' => '_positions',
                'selectedName' => 'position',
                'searchField' => 'searchPosition'
            ],
        ];
    }
}
