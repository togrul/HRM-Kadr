<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Services\WordSuffixService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
            'businessTrips',
            'military.rank'
        ]);

        return view('prints.personnel', compact('personnel'));
    }

    public function cv($personnelId)
    {
        $personnel = Personnel::query()
            ->withCount(['awards', 'punishments'])
            ->with([
                'idDocuments',
                'idDocuments.bornCountry',
                'idDocuments.bornCity',
                'laborActivities',
                'military',
                'educationDegree',
                'specialServices',
                'latestRank.rank',
                'structure',
                'position',
                'education.institution',
                'hasActiveDisposal'
            ])
            ->findOrFail($personnelId);

        Gate::authorize('view', $personnel);

        $suffixService = app(WordSuffixService::class);
        $birthdate = $personnel->birthdate;
        $birthDateYear = optional($birthdate)?->year;
        $structureNames = $personnel->structure?->getAllParentName(isCoded: false) ?? [];
        $structureLabel = collect($structureNames)
            ->map(fn ($structure, $idx) => $suffixService->getStructureSuffix($structure, false, $idx < 1, true) . ' ')
            ->implode('');
        $graduatedYear = $personnel->education?->graduated_year->year;
        $institution = $personnel->education?->institution?->name;
        $hasActiveDisposal = !empty($personnel->hasActiveDisposal) ? 'VMİE' : '';

        $serviceHistory = [
            'military' => $personnel->military
                ? collect($personnel->military)
                    ->sortBy(fn ($service) => optional($service->start_date ?? $service->end_date)?->timestamp ?? 0)
                    ->map(function ($service) use($suffixService) { 
                      return [
                        'location' => $service->location . '-' . $suffixService->getMilitarySuffix($service->location). ' müddətli həqiqi hərbi xidmətdə',
                        'start_date' => optional($service->start_date)?->format('d.m.Y'),
                        'end_date' => optional($service->end_date)?->format('d.m.Y'),
                      ];
                    })
                    ->values()
                    ->all()
                : [],
            'labor' => $personnel->laborActivities
                ? $personnel->laborActivities
                    ->sortBy(fn ($activity) => optional($activity->join_date ?? $activity->leave_date)?->timestamp ?? 0)
                    ->map(function ($activity) use ($suffixService, $hasActiveDisposal) {
                        $company = $suffixService->getStructureSuffix(trim($activity->company_name), false, true, false);
                        $position = trim($activity->position) . " {$hasActiveDisposal}" ;
                        $structureText = trim(implode(' ', array_filter([$company, $position])));

                        return [
                            'structure' => $structureText,
                            'join_date' => optional($activity->join_date)?->format('d.m.Y'),
                            'leave_date' => optional($activity->leave_date)?->format('d.m.Y'),
                        ];
                    })
                    ->values()
                    ->all()
                : [],
        ];

        $cvData = [
            'rank' => $personnel->latestRank?->name,
            'fullname' => $personnel->full_name,
            'structure_label' => trim($structureLabel),
            'position_label' => $suffixService->getMultiSuffix($personnel->position?->name, multi: false),
            'birth' => [
                'day' => optional($birthdate)?->format('d'),
                'month' => optional($birthdate)?->locale('az')->monthName,
                'year' => $birthDateYear . $suffixService->getNumberSuffix($birthDateYear ?? 0),
                'city' => optional($personnel->idDocuments?->bornCity)->name,
            ],
            'photo_url' => $personnel->photo
                ? Storage::url($personnel->photo)
                : null,
            'education' => [
                'degree' => $personnel->educationDegree?->title_az,
                'institution' => $institution . $suffixService->educationSuffix($institution),
                'graduation_year' => $graduatedYear . $suffixService->getNumberSuffix($graduatedYear ?? 0),
            ],
            'awards_count' => $personnel->awards_count > 0 ? ($personnel->awards_count < 10 ? '0' . $personnel->awards_count : $personnel->awards_count) . ' dəfə' : 'Mükafatlandırılmayıb.',
            'punishments_count' => $personnel->punishments_count > 0 ? ($personnel->punishments_count < 10 ? '0' . $personnel->punishments_count : $personnel->punishments_count) . 'dəfə' : "Cəzalandırılmayıb.",
            'family_status' => $personnel->idDocuments?->is_married ? 'Evli.' : 'Subay',
            'address' => $personnel->residental_address,
            'service_history' => $serviceHistory,
            'totalCountLabor' => collect($serviceHistory)->flatten(1)->count(),
            'hasActiveDisposal' => $hasActiveDisposal,
        ];

        return view('prints.cv', compact('cvData'));
    }

    public function print_page($model = null)
    {
        dd(request()->query('headers'));
    }
}
