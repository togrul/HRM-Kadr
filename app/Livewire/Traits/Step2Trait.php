<?php

namespace App\Livewire\Traits;

use App\Models\City;
use App\Models\CountryTranslation;

trait Step2Trait
{
    public $document = [];

    public $documentNationalityName,$documentNationalityId;
    public $documentBornCountryName,$documentBornCountryId;
    public $documentBornCityName,$documentBornCityId,$searchCity;

    public function getDataByPin()
    {
        $pin = $this->document['pin'] ?? '';

        $data = [
            'pin' => $pin
        ];

        if($pin == "1071F12")
        {
            $nationality = CountryTranslation::where('title','LIKE',"%Azərbaycan%")->select('country_id','title')->first();
            $city = City::where('name','LIKE',"%Bakı%")->select('id','name')->first();
            $this->documentNationalityName = $this->documentBornCountryName = $nationality->title;
            $this->documentNationalityId = $this->documentBornCountryId = $nationality->country_id;
            $this->documentBornCityId = $city->id;
            $this->documentBornCityName = $city->name;
            $data = [
                'nationality_id' => [
                    'id' => $this->documentNationalityId,
                    'name' => $this->documentNationalityName
                ],
                'series' => 'AA',
                'number' => 052142,
                'pin' => $this->document['pin'],
                'born_country_id' => [
                    'id' => $this->documentNationalityId,
                    'name' => $this->documentNationalityName
                ],
                'born_city_id' => [
                    'id' => $this->documentBornCityId,
                    'name' => $this->documentBornCityName
                ],
                'registered_address' => 'Bakixanov Sakit Qocayev',
                'is_married' => true,
                'military_duty' => 'h/m',
                'blood_group' => '2+',
                'eye_color' => 'qara',
                'height' => 173,
                'document_issued_authority' => 'ASAN 2',
                'document_issued_date' => '25.05.2020'
            ];
        }
        
        $this->document = $data;
        // $bodyResponse = $response->getBody();
        // $result = json_decode($data);
    }

    public function mountStep2Trait() { 
        $this->documentNationalityName = $this->documentBornCountryName = $this->documentBornCityName = '---';
        !empty($this->personnelModel) && $this->fillIdDocument();
    }

    protected function fillIdDocument()
    {
        if(!empty($this->personnelModelData->idDocuments))
        {
            $updateIdDocument = $this->personnelModelData->idDocuments->load(['nationality','bornCountry','bornCity'])->toArray();

            if(!empty($updateIdDocument))
            {
                $this->document = [
                    'pin' =>  $updateIdDocument['pin'],
                    'series' =>  $updateIdDocument['series'],
                    'number' =>  $updateIdDocument['number'],
                    'registered_address' =>  $updateIdDocument['registered_address'],
                    'is_married' =>  $updateIdDocument['is_married'],
                    'military_duty' =>  $updateIdDocument['military_duty'],
                    'blood_group' =>  $updateIdDocument['blood_group'],
                    'eye_color' =>  $updateIdDocument['eye_color'],
                    'height' =>  $updateIdDocument['height'],
                    'document_issued_authority' =>  $updateIdDocument['document_issued_authority'],
                    'document_issued_date' =>  $updateIdDocument['document_issued_date'],
                ];
                if(!empty($updateIdDocument['nationality_id']))
                {
                    $this->document['nationality_id'] = [
                        'id' => $updateIdDocument['nationality']['id'],
                        'title' => $updateIdDocument['nationality']['title'],
                    ];
                    $this->documentNationalityId = $updateIdDocument['nationality']['id'];
                    $this->documentNationalityName = $updateIdDocument['nationality']['title'];
                }
                if(!empty($updateIdDocument['born_country_id']))
                {
                    $this->document['born_country_id'] = [
                        'id' => $updateIdDocument['born_country']['id'],
                        'title' => $updateIdDocument['born_country']['title'],
                    ];
                    $this->documentBornCountryId = $updateIdDocument['born_country']['id'];
                    $this->documentBornCountryName = $updateIdDocument['born_country']['title'];
                }
                if(!empty($updateIdDocument['born_city_id']))
                {
                    $this->document['born_city_id'] = [
                        'id' => $updateIdDocument['born_city']['id'],
                        'name' => $updateIdDocument['born_city']['name'],
                    ];
                    $this->documentBornCityId = $updateIdDocument['born_city']['id'];
                    $this->documentBornCityName = $updateIdDocument['born_city']['name'];
                }
            }
        }
        
    }
}