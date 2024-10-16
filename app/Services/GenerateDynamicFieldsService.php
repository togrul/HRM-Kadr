<?php

namespace App\Services;

class GenerateDynamicFieldsService
{
    public function handle(): array
    {
        return [
            '$fullname' => [
                'field' => 'personnel_id',
                'title' => __('Select personnel'),
                'model' => '_personnels',
                'selectedName' => 'personnel',
                'searchField' => 'searchPersonnel',
            ],
            '$rank' => [
                'field' => 'rank_id',
                'title' => __('Select rank'),
                'model' => '_ranks',
                'selectedName' => 'rank',
            ],
            '$day' => [
                'field' => 'day',
                'title' => __('Day'),
            ],
            '$month' => [
                'field' => 'month',
                'title' => __('Month'),
            ],
            '$year' => [
                'field' => 'year',
                'title' => __('Year'),
            ],
            '$name' => [
                'field' => 'name',
                'title' => __('Name'),
            ],
            '$surname' => [
                'field' => 'surname',
                'title' => __('Surname'),
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
                'searchField' => 'searchStructure',
            ],

            '$position' => [
                'field' => 'position_id',
                'title' => __('Select position'),
                'model' => '_positions',
                'selectedName' => 'position',
                'searchField' => 'searchPosition',
            ],

            '$start_date' => [
                'field' => 'start_date',
                'title' => __('Start date'),
            ],

            '$end_date' => [
                'field' => 'end_date',
                'title' => __('End date'),
            ],

            '$days' => [
                'field' => 'days',
                'title' => __('Day'),
            ],

            '$location' => [
                'field' => 'location',
                'title' => __('Location'),
            ],
            //tercume elemek burdan asagidakilari.lang faylina.!!!!!!!!
            '$trip_start_day' => [
                'field' => 'trip_start_day',
                'title' => __('Trip start day'),
            ],

            '$trip_start_month' => [
                'field' => 'trip_start_month',
                'title' => __('Trip start month'),
            ],

            '$trip_start_year' => [
                'field' => 'trip_start_year',
                'title' => __('Trip start year'),
            ],

            '$transportation' => [
                'field' => 'transportation',
                'title' => __('Select transportation'),
                'model' => '_transportations',
                'selectedName' => 'transportation',
            ],

            '$meeting_hour' => [
                'field' => 'meeting_hour',
                'title' => __('Meeting Hour'),
            ],

            '$return_month' => [
                'field' => 'return_month',
                'title' => __('Return Month'),
            ],

            '$return_day' => [
                'field' => 'return_day',
                'title' => __('Return day'),
            ],

            '$weapon' => [
                'field' => 'weapon',
                'title' => __('Weapon'),
            ],

            '$car' => [
                'field' => 'car',
                'title' => __('Car'),
            ],
        ];
    }
}
