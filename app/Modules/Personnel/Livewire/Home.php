<?php

namespace App\Modules\Personnel\Livewire;

use App\Modules\Personnel\Application\Services\HomeOverviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The attention tiles and the today rail render with the page; the attendance chart,
 * activity feed and structure coverage are lazy islands, so each block's read only
 * runs when that island is requested.
 *
 * @property-read list<array<string,mixed>> $attention
 */
class Home extends Component
{
    /**
     * @return list<array<string,mixed>>
     */
    #[Computed]
    public function attention(): array
    {
        return $this->overview()->attention(auth()->user());
    }

    /**
     * @return list<array{key:string,count:int,accent:string,note:string|null,route:string|null}>
     */
    #[Computed]
    public function today(): array
    {
        return $this->overview()->today(auth()->user(), $this->attention);
    }

    /**
     * @return list<array<string,mixed>>
     */
    #[Computed]
    public function attendanceWeek(): array
    {
        return $this->overview()->attendanceWeek(auth()->user());
    }

    /**
     * @return list<array{id:int,event:string,subject:string,subject_id:int|null,actor:string,at:\Carbon\Carbon|null}>
     */
    #[Computed]
    public function activity(): array
    {
        return $this->overview()->activity(auth()->user());
    }

    /**
     * @return list<array{id:int,name:string,total:int,filled:int,vacant:int,pct:int}>
     */
    #[Computed]
    public function structureFill(): array
    {
        return $this->overview()->structureFill(auth()->user());
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.home');
    }

    private function overview(): HomeOverviewService
    {
        return app(HomeOverviewService::class);
    }
}
