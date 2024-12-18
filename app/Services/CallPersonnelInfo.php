<?php

namespace App\Services;

use App\Enums\KnowledgeStatusEnum;
use App\Models\Award;
use App\Models\City;
use App\Models\Country;
use App\Models\Disability;
use App\Models\EducationalInstitution;
use App\Models\EducationDegree;
use App\Models\EducationDocumentType;
use App\Models\EducationForm;
use App\Models\EducationType;
use App\Models\Kinship;
use App\Models\Language;
use App\Models\Position;
use App\Models\Punishment;
use App\Models\Rank;
use App\Models\RankReason;
use App\Models\ScientificDegreeAndName;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\WorkNorm;
use Illuminate\Support\Facades\DB;

class CallPersonnelInfo
{
    public function getAll($isDisability, $_this): array
    {
        $nationalities = Country::withWhereHas('currentCountryTranslations', function ($query) use ($_this) {
            $query
                ->when(! empty($_this->searchNationality), function ($q) use ($_this) {
                    $q->where('title', 'LIKE', "%$_this->searchNationality%");
                })
                ->when(! empty($_this->searchPreviousNationality), function ($q) use ($_this) {
                    $q->where('title', 'LIKE', "%$_this->searchPreviousNationality%");
                });
        })
            ->get()
            ->sortBy('currentCountryTranslations.title')
            ->all();

        $education_degrees = EducationDegree::select('id', DB::raw('title_'.config('app.locale').' as title'))
            ->when(! empty($_this->searchEducationDegree), function ($q) use ($_this) {
                $q->where('title_'.config('app.locale'), 'LIKE', "%$_this->searchEducationDegree%");
            })
            ->get();

        $structures = Structure::when(! empty($_this->searchStructure), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchStructure%");
        })
            ->accessible()
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        $positions = Position::when(! empty($_this->searchPosition), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchPosition%");
        })
            ->get();

        $work_norms = WorkNorm::select('id', DB::raw('name_'.config('app.locale').' as name'))
            ->when(! empty($_this->searchWorkNorm), function ($q) use ($_this) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchWorkNorm%");
            })
            ->get();

        $disabilities = $isDisability
            ? Disability::when(! empty($_this->searchDisability), function ($q) use ($_this) {
                $q->where('name', 'LIKE', "%$_this->searchDisability%");
            })
                ->get()
            : [];

        $institutions = EducationalInstitution::when(! empty($_this->searchInstitution), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchInstitution%");
        })
            ->when(! empty($_this->searchExtraInstitution), function ($q) use ($_this) {
                $q->where('name', 'LIKE', "%$_this->searchExtraInstitution%");
            })
            ->get();

        $education_forms = EducationForm::select('id', DB::raw('name_'.config('app.locale').' as name'))
            ->when(! empty($_this->searchEducationForm), function ($q) use ($_this) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchEducationForm%");
            })
            ->when(! empty($_this->searchExtraEducationForm), function ($q) use ($_this) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchExtraEducationForm%");
            })
            ->get();

        $education_types = EducationType::when(! empty($_this->searchEducationType), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchEducationType%");
        })
            ->get();

        $document_types = EducationDocumentType::when(! empty($_this->searchDocumentTyoe), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchDocumentTyoe%");
        })
            ->get();

        $rankModel = Rank::query()
            ->when(! empty($_this->searchRank), function ($q) use ($_this) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchRank%");
            })
            ->when(! empty($_this->searchMilitaryRank), function ($q) use ($_this) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchMilitaryRank%");
            })
            ->where('is_active', 1)
            ->get();

        $rankReasons = RankReason::all();

        $awardModel = Award::when(! empty($_this->searchAward), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchAward%");
        })
            ->get();

        $punishmentModel = Punishment::when(! empty($_this->searchPunishment), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchPunishment%");
        })
            ->criminalType('other')
            ->orderBy('name')
            ->get();

        $criminalModel = Punishment::when(! empty($_this->searchCriminal), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchCriminal%");
        })
            ->criminalType('criminal')
            ->orderBy('name')
            ->get();

        $kinshipModel = Kinship::select('id', DB::raw('name_'.config('app.locale').' as name'), 'is_active')
            ->when(! empty($_this->searchKinship), function ($q) use ($_this) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchKinship%");
            })
            ->where('is_active', 1)
            ->get();

        $cities = City::select('id', 'name', 'country_id')
            ->when(! empty($_this->searchCity), function ($q) use ($_this) {
                $q->where('name', 'LIKE', "%$_this->searchCity%");
            })
            ->when(! empty($_this->documentBornCountryId), function ($q) use ($_this) {
                $q->where('country_id', $_this->documentBornCountryId);
            })
            ->get();

        $_social_origins = SocialOrigin::when(! empty($_this->searchSocialOrigin), function ($q) use ($_this) {
            $q->where('name', 'LIKE', "%$_this->searchSocialOrigin%");
        })->get();

        return [
            'nationalities' => $nationalities,
            'education_degrees' => $education_degrees,
            'structures' => $structures,
            'positions' => $positions,
            'work_norms' => $work_norms,
            'disabilities' => $disabilities,
            'institutions' => $institutions,
            'education_forms' => $education_forms,
            'education_types' => $education_types,
            'document_types' => $document_types,
            'rankModel' => $rankModel,
            'rankReasons' => $rankReasons,
            'awardModel' => $awardModel,
            'punishmentModel' => $punishmentModel,
            'criminalModel' => $criminalModel,
            'kinshipModel' => $kinshipModel,
            'cities' => $cities,
            '_social_origins' => $_social_origins,
        ];
    }
}
