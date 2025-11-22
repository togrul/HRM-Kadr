<?php

namespace App\Modules\Admin\Providers;

use App\Models\City;
use App\Models\CountryTranslation;
use App\Models\Disability;
use App\Models\EducationDegree;
use App\Models\Position;
use App\Models\RankReason;
use App\Models\SocialOrigin;
use App\Models\WorkNorm;
use App\Observers\CityObserver;
use App\Observers\CountryTranslationObserver;
use App\Observers\DisabilityObserver;
use App\Observers\EducationDegreeObserver;
use App\Observers\PositionObserver;
use App\Observers\RankReasonObserver;
use App\Observers\SocialOriginObserver;
use App\Observers\WorkNormObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'admin');
        $this->registerObservers();
        $this->registerLivewireComponents();
    }

    protected function registerObservers(): void
    {
        CountryTranslation::observe(CountryTranslationObserver::class);
        City::observe(CityObserver::class);
        Position::observe(PositionObserver::class);
        Disability::observe(DisabilityObserver::class);
        RankReason::observe(RankReasonObserver::class);
        SocialOrigin::observe(SocialOriginObserver::class);
        EducationDegree::observe(EducationDegreeObserver::class);
        WorkNorm::observe(WorkNormObserver::class);
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'admin');
    }

    protected function componentMap(): array
    {
        return [
            'appeal-statuses' => \App\Modules\Admin\Livewire\AppealStatus::class,
            'award-types' => \App\Modules\Admin\Livewire\AwardTypes::class,
            'awards' => \App\Modules\Admin\Livewire\Awards::class,
            'cities' => \App\Modules\Admin\Livewire\Cities::class,
            'countries' => \App\Modules\Admin\Livewire\Countries::class,
            'dashboard' => \App\Modules\Admin\Livewire\Dashboard::class,
            'document-types' => \App\Modules\Admin\Livewire\DocumentTypes::class,
            'education-degrees' => \App\Modules\Admin\Livewire\EducationDegrees::class,
            'education-forms' => \App\Modules\Admin\Livewire\EducationForms::class,
            'education-types' => \App\Modules\Admin\Livewire\EducationTypes::class,
            'educational-institutions' => \App\Modules\Admin\Livewire\EducationalInstitutions::class,
            'kinships' => \App\Modules\Admin\Livewire\Kinships::class,
            'languages' => \App\Modules\Admin\Livewire\Languages::class,
            'leave-types' => \App\Modules\Admin\Livewire\LeaveTypes::class,
            'order-categories' => \App\Modules\Admin\Livewire\OrderCategories::class,
            'order-statuses' => \App\Modules\Admin\Livewire\OrderStatuses::class,
            'positions' => \App\Modules\Admin\Livewire\Positions::class,
            'punishments' => \App\Modules\Admin\Livewire\Punishments::class,
            'rank-categories' => \App\Modules\Admin\Livewire\RankCategories::class,
            'rank-reasons' => \App\Modules\Admin\Livewire\RankReasons::class,
            'scientific-degrees' => \App\Modules\Admin\Livewire\ScientificDegrees::class,
            'social-origins' => \App\Modules\Admin\Livewire\SocialOrigins::class,
            'structures' => \App\Modules\Admin\Livewire\Structures::class,
            'weapons' => \App\Modules\Admin\Livewire\Weapons::class,
            'work-norms' => \App\Modules\Admin\Livewire\WorkNorms::class,
        ];
    }
}
