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
        public ?array $selected_personnel_list
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
        $_personnel_list_by_name = $this->getPersonnelNameListByName('vacation');

        return compact('_personnel_list_by_name');
    }

    private function getBusinessTripsBladeCollections(): array
    {
        $_personnel_list_by_name = $this->getPersonnelNameListByName('business_trips');

        $_transportationList = TransportationEnum::values();

        $_transportations = array_map(fn ($value, $key) => [
            'id' => $key + 1,
            'name' => $value,
        ], $_transportationList, array_keys($_transportationList));

        $_weapons = Weapon::all();

        return [
            '_transportationList' => $_transportationList,
            '_transportations' => $_transportations,
            '_weapons' => $_weapons,
            '_personnel_list_by_name' => $_personnel_list_by_name,
        ];
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
                 ->whereNotIn('tabel_no', $this->selected_personnel_list['personnels'])
                 ->get()
             : [];
    }
}
