<?php

namespace App\Services;

use App\Enums\TransportationEnum;
use App\Models\Order;
use App\Models\Personnel;
use App\Models\Weapon;

class OrderCollectionListsService
{
    public function __construct(
        public ?string $selectedBlade,
        public ?string $personnel_name,
        public array $selectedPersonnelNumbers = []
    ) {}

    public function handle(): array
    {
        return match ($this->selectedBlade) {
            Order::BLADE_VACATION => $this->getVacationBladeCollections(),
            Order::BLADE_BUSINESS_TRIP => $this->getBusinessTripsBladeCollections(),
            default => [],
        };
    }

    private function getVacationBladeCollections(): array
    {
        return [
            '_personnel_list_by_name' => $this->getPersonnelNameListByName('vacation')
        ];
    }

    private function getBusinessTripsBladeCollections(): array
    {
        return [
            '_transportationList' => TransportationEnum::values(),
            '_transportations' => $this->formatTransportations(),
            '_weapons' => Weapon::all(),
            '_personnel_list_by_name' => $this->getPersonnelNameListByName('business_trips'),
        ];
    }

    private function formatTransportations(): array
    {
        return collect(TransportationEnum::values())
            ->map(fn ($value, $key) => [
                'id' => $key + 1,
                'name' => $value,
            ])
            ->toArray();
    }

    private function getPersonnelNameListByName(string $blade)
    {
        $with = match ($blade) {
            'vacation' => 'inActiveVacation',
            'business_trips' => 'inActiveBusinessTrip',
            default => '',
        };

        return strlen($this->personnel_name) > 2
             ? Personnel::with($with)
                 ->when(! empty($this->personnel_name), function ($q) {
                     $q->where(function ($query) {
                         $query->where('name', 'LIKE', "%{$this->personnel_name}%")
                             ->orWhere('surname', 'LIKE', "%{$this->personnel_name}%");
                     });
                 })
                 ->active()
                 ->whereNull('deleted_at')
                 ->whereNotIn('tabel_no', $this->selectedPersonnelNumbers)
                 ->get()
             : [];
    }
}
