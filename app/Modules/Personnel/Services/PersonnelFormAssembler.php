<?php

namespace App\Modules\Personnel\Services;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\PersonalInformationForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use Illuminate\Support\Arr;

class PersonnelFormAssembler
{
    /**
     * Normalize all form state into persistence-ready payloads.
     *
     * @param  array<int, string>  $dateFields
     * @param  callable(array<string, mixed>, array<int, string>): array<string, mixed>  $dateNormalizer
     * @return array{
     *     personnel_data: array<string, mixed>,
     *     personnel_extra: array<string, mixed>,
     *     relation_payloads: array<string, mixed>
     * }
     */
    public function buildForStore(
        PersonalInformationForm $personalForm,
        DocumentForm $documentForm,
        EducationForm $educationForm,
        LaborActivityForm $laborActivityForm,
        ServiceHistoryForm $historyForm,
        AwardsPunishmentsForm $awardsPunishmentsForm,
        KinshipForm $kinshipForm,
        MiscellaneousForm $miscForm,
        array $dateFields,
        callable $dateNormalizer,
        ?bool $forcePending = null
    ): array {
        $personalPayload = $personalForm->toPayload();
        $personnelData = $dateNormalizer($personalPayload['personnel'] ?? [], $dateFields);

        if (! is_null($forcePending)) {
            $personnelData['is_pending'] = $forcePending;
        }

        $documentPayload = $documentForm->toPayload();
        $relationPayloads = [
            'document' => data_get($documentPayload, 'document', []),
            'service_cards' => data_get($documentPayload, 'service_cards.list', []),
            'passports' => data_get($documentPayload, 'passports.list', []),
            'education' => $educationForm->educationForPersistence(),
            'extra_educations' => $educationForm->extraEducationsForPersistence(),
            'labor_activities' => $laborActivityForm->laborActivitiesForPersistence(),
            'ranks' => $laborActivityForm->ranksForPersistence(),
            'military' => $historyForm->militaryForPersistence(),
            'injuries' => $historyForm->injuriesForPersistence(),
            'captivities' => $historyForm->captivitiesForPersistence(),
            'awards' => $awardsPunishmentsForm->awardsForPersistence(),
            'punishments' => $awardsPunishmentsForm->punishmentsForPersistence(),
            'kinships' => $kinshipForm->kinshipsForPersistence(),
            'languages' => $miscForm->languagesForPersistence(),
            'events' => $miscForm->eventsForPersistence(),
            'degrees' => $miscForm->degreesForPersistence(),
            'elections' => $miscForm->electionsForPersistence(),
        ];

        return [
            'personnel_data' => $personnelData,
            'personnel_extra' => Arr::wrap($personalPayload['personnel_extra'] ?? []),
            'relation_payloads' => $relationPayloads,
        ];
    }
}
