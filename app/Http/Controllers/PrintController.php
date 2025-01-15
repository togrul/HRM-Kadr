<?php

namespace App\Http\Controllers;

use App\Models\Personnel;

class PrintController extends Controller
{
    public function personnel_service_book($personnelId)
    {
        $personnel = Personnel::findOrFail($personnelId);
        $personnel->load([
            'nationality',
            'previousNationality',
            'idDocuments',
            'idDocuments.bornCountry',
            'idDocuments.bornCity',
            'laborActivities',
            'specialServices',
            'foreignLanguages',
            'foreignLanguages.language',
            'educationDegree',
            'education',
            'education.institution',
            'extraEducations',
            'extraEducations.institution',
            'latestRank.rank',
            'ranksASC',
            'ranksASC.rank',
            'awards',
            'awards.award',
            'injuries',
            'structure',
            'position',
            'socialOrigin',
            'degreeAndNames',
            'degreeAndNames.degreeAndName',
            'elections',
            'captives',
            'fatherMother',
            'fatherMother.kinship',
            'wifeChildren',
            'wifeChildren.kinship',
            'businessTrips'
        ]);

        return view('prints.personnel', compact('personnel'));
    }

    public function print_page($model = null)
    {
        dd(request()->query('headers'));
    }
}
