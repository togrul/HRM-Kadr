<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CountryTranslation;

class DocumentController extends Controller
{
    public function index(string $pin)
    {
//        dd('sds');
        $data = [];
        if ($pin == '1071F12') {
            $data = [
                'nationality_id' => CountryTranslation::where('title', 'LIKE', '%Azərbaycan%')->value('country_id'),
                'series' => 'AA',
                'number' => '052142',
                'pin' => $pin,
                'born_country_id' => CountryTranslation::where('title', 'LIKE', '%Azərbaycan%')->value('country_id'),
                'born_city_id' => City::where('name', 'LIKE', '%Azərbaycan%')->value('id'),
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

        return json_encode($data);
    }
}
