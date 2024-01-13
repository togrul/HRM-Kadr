<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;

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
            'awards',
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
            'wifeChildren.kinship'
        ]);

        return view('prints.personnel',compact('personnel'));
    }
}
