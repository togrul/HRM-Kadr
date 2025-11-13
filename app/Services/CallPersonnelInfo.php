<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Disability;
use App\Models\EducationDegree;
use App\Models\Position;
use App\Models\RankReason;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\WorkNorm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CallPersonnelInfo
{
    /**
     * Flush cached dropdown payloads. Useful after seeders or manual CRUD.
     */
    public static function flush(?int $userId = null, ?string $locale = null): void
    {
        $service = resolve(static::class);
        $locale ??= config('app.locale');
        $userId ??= auth()->id();

        Cache::forget($service->cacheKey('nationalities'));
        Cache::forget($service->cacheKey('positions'));
        Cache::forget($service->cacheKey('disabilities'));
        Cache::forget($service->cacheKey('rank_reasons'));
        Cache::forget($service->cacheKey('social_origins'));

        if ($locale) {
            Cache::forget($service->cacheKey('education_degrees', $locale));
            Cache::forget($service->cacheKey('work_norms', $locale));
        }

        if ($userId) {
            Cache::forget($service->cacheKey('structures', $userId));
        }
    }

    public function getAll($isDisability, $_this): array
    {
        $nationalities = $this->rememberCollection(
            $this->cacheKey('nationalities'),
            function () use ($_this) {
                return Country::withWhereHas('currentCountryTranslations', function ($query) use ($_this) {
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
            },
            empty($_this->searchNationality) && empty($_this->searchPreviousNationality)
        );

        $education_degrees = $this->rememberCollection(
            $this->cacheKey('education_degrees', config('app.locale')),
            function () use ($_this) {
                return EducationDegree::select('id', DB::raw('title_'.config('app.locale').' as title'))
                    ->when(! empty($_this->searchEducationDegree), function ($q) use ($_this) {
                        $q->where('title_'.config('app.locale'), 'LIKE', "%$_this->searchEducationDegree%");
                    })
                    ->get();
            },
            empty($_this->searchEducationDegree)
        );

        $structures = $this->rememberCollection(
            $this->cacheKey('structures', auth()->id()),
            function () use ($_this) {
                return Structure::accessible()
                    ->when(! empty($_this->searchStructure), function ($q) use ($_this) {
                        $q->where('name', 'LIKE', "%$_this->searchStructure%");
                    })
                    ->orderBy('level')
                    ->orderBy('code')
                    ->get();
            },
            empty($_this->searchStructure)
        );

        $positions = $this->rememberCollection(
            $this->cacheKey('positions'),
            function () use ($_this) {
                return Position::when(! empty($_this->searchPosition), function ($q) use ($_this) {
                    $q->where('name', 'LIKE', "%$_this->searchPosition%");
                })->get();
            },
            empty($_this->searchPosition)
        );

        $work_norms = $this->rememberCollection(
            $this->cacheKey('work_norms', config('app.locale')),
            function () use ($_this) {
                return WorkNorm::select('id', DB::raw('name_'.config('app.locale').' as name'))
                    ->when(! empty($_this->searchWorkNorm), function ($q) use ($_this) {
                        $q->where('name_'.config('app.locale'), 'LIKE', "%$_this->searchWorkNorm%");
                    })
                    ->get();
            },
            empty($_this->searchWorkNorm)
        );

        $disabilities = [];

        if ($isDisability) {
            $disabilities = $this->rememberCollection(
                $this->cacheKey('disabilities'),
                function () use ($_this) {
                    return Disability::when(! empty($_this->searchDisability), function ($q) use ($_this) {
                        $q->where('name', 'LIKE', "%$_this->searchDisability%");
                    })->get();
                },
                empty($_this->searchDisability)
            );
        }

        $rankReasons = $this->rememberCollection(
            $this->cacheKey('rank_reasons'),
            fn () => RankReason::all()
        );

        $_social_origins = $this->rememberCollection(
            $this->cacheKey('social_origins'),
            function () use ($_this) {
                return SocialOrigin::when(! empty($_this->searchSocialOrigin), function ($q) use ($_this) {
                    $q->where('name', 'LIKE', "%$_this->searchSocialOrigin%");
                })->get();
            },
            empty($_this->searchSocialOrigin)
        );

        return [
            'nationalities' => $nationalities,
            'education_degrees' => $education_degrees,
            'structures' => $structures,
            'positions' => $positions,
            'work_norms' => $work_norms,
            'disabilities' => $disabilities,
            'rankReasons' => $rankReasons,
            '_social_origins' => $_social_origins,
        ];
    }

    protected function rememberCollection(string $cacheKey, callable $resolver, bool $useCache = true)
    {
        if (! $useCache) {
            return $resolver();
        }

        return Cache::rememberForever($cacheKey, $resolver);
    }

    protected function cacheKey(string $prefix, $suffix = null): string
    {
        $parts = array_filter([$prefix, $suffix]);

        return implode(':', $parts);
    }
}
