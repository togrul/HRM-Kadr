<?php

namespace App\Livewire\Traits;

use App\Models\City;
use App\Models\CountryTranslation;

trait Step2Trait
{
    public $document = [];

    public $documentNationalityName;

    public $documentNationalityId;

    public $documentBornCountryName;

    public $documentBornCountryId;

    public $documentBornCityName;

    public $documentBornCityId;

    public $searchCity;

    public $service_cards_list = [];
    public $service_cards = [];

    public function getDataByPin()
    {
        $pin = $this->document['pin'] ?? '';

        $data = [
            'pin' => $pin,
        ];

        if ($pin == '1071F12') {
            $nationality = CountryTranslation::where('title', 'LIKE', '%Azərbaycan%')->select('country_id', 'title')->first();
            $city = City::where('name', 'LIKE', '%Bakı%')->select('id', 'name')->first();
            $this->documentNationalityName = $this->documentBornCountryName = $nationality->title;
            $this->documentNationalityId = $this->documentBornCountryId = $nationality->country_id;
            $this->documentBornCityId = $city->id;
            $this->documentBornCityName = $city->name;
            $data = [
                'nationality_id' => [
                    'id' => $this->documentNationalityId,
                    'name' => $this->documentNationalityName,
                ],
                'series' => 'AA',
                'number' => 052142,
                'pin' => $this->document['pin'],
                'born_country_id' => [
                    'id' => $this->documentNationalityId,
                    'name' => $this->documentNationalityName,
                ],
                'born_city_id' => [
                    'id' => $this->documentBornCityId,
                    'name' => $this->documentBornCityName,
                ],
                'registered_address' => 'Bakixanov Sakit Qocayev',
                'is_married' => true,
                'military_duty' => 'h/m',
                'blood_group' => '2+',
                'eye_color' => 'qara',
                'height' => 173,
                'document_issued_authority' => 'ASAN 2',
                'document_issued_date' => '25.05.2020',
            ];
        }

        $this->document = $data;
        // $bodyResponse = $response->getBody();
        // $result = json_decode($data);
    }

    public function addServiceCard() {
        $this->validate($this->exceptArray('document'));
        $this->service_cards_list[] = $this->service_cards;
        $this->service_cards = [];
    }

    public function forceDeleteServiceCard($key) {
        unset($this->service_cards_list[$key]);
    }

    public function mountStep2Trait()
    {
        $this->documentNationalityName = $this->documentBornCountryName = $this->documentBornCityName = '---';
        if(! empty($this->personnelModel))
        {
            $this->fillIdDocument();
            $this->fillServiceCards();
        }
    }

    protected function fillIdDocument()
    {
        if (! empty($this->personnelModelData->idDocuments)) {
            $updateIdDocument = $this->personnelModelData->idDocuments
                            ->load(['nationality', 'bornCountry', 'bornCity'])
                            ->toArray();

            if (! empty($updateIdDocument)) {
                $this->document = $this->mapAttributes(
                    attributes: [
                        'pin', 'series', 'number', 'registered_address', 'is_married',
                        'military_duty', 'blood_group', 'eye_color',
                        'height', 'document_issued_authority', 'document_issued_date',
                    ], getFrom: $updateIdDocument
                );

                $this->handleRelatedEntity(entity: 'nationality', field: 'nationality_id', fillTo: 'document', getFrom: $updateIdDocument, differentSelectInput: 'documentNationality');
                $this->handleRelatedEntity(entity: 'born_country', field: 'born_country_id', fillTo: 'document', getFrom: $updateIdDocument, differentSelectInput: 'documentBornCountry');
                $this->handleRelatedEntity(entity: 'born_city', field: 'born_city_id', fillTo: 'document', getFrom: $updateIdDocument, titleField: 'name', differentSelectInput: 'documentBornCity');
            }
        }
    }

    protected function fillServiceCards()
    {
        $updateServiceCards = $this->personnelModelData->cards->toArray();
        if (! empty($updateServiceCards)) {
            foreach ($updateServiceCards as $key => $uptServiceCard) {
                $this->service_cards_list[] = $this->mapAttributes(
                    attributes: [
                        'card_number', 'valid_date',
                    ],
                    getFrom: $uptServiceCard
                );
            }
        }
    }
}
