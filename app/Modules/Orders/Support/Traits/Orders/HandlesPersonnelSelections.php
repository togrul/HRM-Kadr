<?php

namespace App\Modules\Orders\Support\Traits\Orders;

use App\Enums\StructureEnum;
use App\Helpers\UsefulHelpers;
use App\Models\Order;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Services\WordSuffixService;
use Carbon\Carbon;

trait HandlesPersonnelSelections
{
    public ?string $personnel_name = null;

    public function addToList(string $tabelNo, int $row): void
    {
        $this->validate($this->validationRules()['dynamic']);

        $person = Personnel::with(['latestRank.rank', 'idDocuments', 'validPassport', 'structure', 'position', 'activeWeapons', 'activeWeapons.weapon'])
            ->where('tabel_no', $tabelNo)
            ->first();

        if (! $person) {
            return;
        }

        $data = [
            'row' => $row,
            'key' => $tabelNo,
            'rank' => $person->latestRank?->rank->name,
            'fullname' => $person->fullname,
        ];

        switch ($this->selectedBlade) {
            case Order::BLADE_VACATION:
                $person->load(['currentWork', 'yearlyVacation']);
                $data = array_merge($data, $this->vacationPayload($person, $row));
                break;
            case Order::BLADE_BUSINESS_TRIP:
                $data = array_merge($data, $this->businessTripPayload($person));
                break;
        }

        $this->selectedPersonnel->add($row, $data);

        $this->reset('personnel_name');
    }

    public function removeFromList(int $index, int $row): void
    {
        $this->selectedPersonnel->remove($row, $index);
    }

    protected function fillPersonnelsToComponents(string $selectedBlade): array
    {
        $rows = $this->selectedPersonnel->flattenedRows();
        $payload = [];

        foreach ($rows as $key => $selection) {
            $row = $selection['row'];
            $componentRow = $this->componentForms[$row];

            $columns = [
                'fullname' => $selection['fullname'],
                'rank' => $selection['rank'],
                'structure' => $selection['structure'],
                'start_date' => $componentRow['start_date'],
                'end_date' => $componentRow['end_date'],
                'component_id' => $componentRow['component_id'],
                'row' => $row,
                'position' => $selection['position'],
                'location' => $selection['location'] ?? '',
            ];

            $payload[$key] = $this->mergeBladeSpecificData($selectedBlade, $columns, $selection, $componentRow);
        }

        return $payload;
    }

    protected function mergeBladeSpecificData(string $blade, array $columns, array $selection, array $componentRow): array
    {
        return match ($blade) {
            Order::BLADE_VACATION => array_merge($columns, [
                'days' => $componentRow['days'],
                'vacation_days_total' => $selection['vacation_days_total'],
                'vacation_days_remaining' => $selection['vacation_days_total'] - $componentRow['days'],
                'reserved_date_month' => $selection['reserved_date_month'],
                'work_duration' => $selection['work_duration'],
            ]),
            Order::BLADE_BUSINESS_TRIP => $this->mergeBusinessTripData($columns, $selection, $componentRow),
            default => $columns,
        };
    }

    protected function mergeBusinessTripData(array $columns, array $selection, array $componentRow): array
    {
        if ($this->selectedTemplate == PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP) {
            $columns['meeting_hour'] = $componentRow['meeting_hour'];
            $columns['return_month'] = $componentRow['return_month'];
            $columns['return_day'] = $componentRow['return_day'];
            $columns['transportation'] = $selection['transportation'] ?? [];
            $columns['car'] = $selection['car'] ?? '';
            $columns['weapon'] = $selection['weapon'];
            $columns['bullet'] = $selection['bullet'] ?? 32;
            $columns['service_dog'] = $selection['service_dog'] ?? false;
        } else {
            $columns['location'] = $componentRow['location'];
        }

        $columns['passport'] = $selection['passport'] ?? '';

        return $columns;
    }

    protected function vacationPayload(Personnel $person, int $row): array
    {
        $person->loadMissing(['currentWork', 'yearlyVacation']);
        $vacation = $person->yearlyVacation[0] ?? null;
        $remaining = $vacation?->remaining_days ?? 0;
        $daysRequested = $this->componentForms[$row]['days'] ?? 0;

        if ($vacation && ($remaining < 1 || $daysRequested > $remaining)) {
            $this->dispatch('checkVacationAdd', __('There are not enough days left for this vacation.'));
        }

        $workDuration = $person->currentWork?->join_date?->diffInMonths(Carbon::now()) ?? 0;
        if ($workDuration < 6) {
            $this->dispatch('addError', $person->fullname . ' 6 aydan az müddətdir işləyir.');
        }

        return [
            'vacation_days_total' => $vacation?->vacation_days_total ?? 0,
            'vacation_days_remaining' => $remaining,
            'reserved_date_month' => $vacation ? array_search($vacation->reserved_date_month, UsefulHelpers::monthsList(config('app.locale')), true) : null,
            'work_duration' => $workDuration,
            'position' => $person->position->name,
            'structure' => $person->structure->name,
        ];
    }

    protected function businessTripPayload(Personnel $person): array
    {
        $data = [
            'position' => $person->position->name,
            'structure' => $this->getStructureFull($person->structure),
        ];

        if ($this->selectedTemplate == PersonnelBusinessTrip::INTERNAL_BUSINESS_TRIP) {
            $personWeapons = collect($person->activeWeapons)
                ->map(fn ($activeWeapon) => sprintf('%s №_%s', $activeWeapon->weapon->name, $activeWeapon->weapon_serial))
                ->implode(' ');
            $data['passport'] = $person->idDocuments->serialNumber ?? '';
            $data['weapon'] = $personWeapons;
        } else {
            $data['passport'] = $person->validPassport->serial_number ?? '';
        }

        return $data;
    }

    protected function getStructureFull($structure): string
    {
        $structureName = $structure?->topLevelParent() ?? $structure->name;
        $suffixService = new WordSuffixService;

        $levels = array_column(StructureEnum::cases(), 'name', 'value');
        $levelName = __(strtolower($levels[$structure->level]) ?? '');

        return is_numeric($structureName)
            ? sprintf('%s%s %s', $structureName, $suffixService->getNumberSuffix((int) $structureName), $levelName)
            : $structureName;
    }
}
