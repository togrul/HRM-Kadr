<?php

namespace App\Services;

use App\Traits\NormalizesDropdownPayloads;
use App\Models\Personnel;
use App\Models\PersonnelAward;
use App\Models\PersonnelCard;
use App\Models\PersonnelEducation;
use App\Models\PersonnelElectedElectoral;
use App\Models\PersonnelExtraEducation;
use App\Models\PersonnelIdentityDocument;
use App\Models\PersonnelInjury;
use App\Models\PersonnelKinship;
use App\Models\PersonnelLaborActivity;
use App\Models\PersonnelMilitaryService;
use App\Models\PersonnelParticipationEvent;
use App\Models\PersonnelPassports;
use App\Models\PersonnelPunishment;
use App\Models\PersonnelRank;
use App\Models\PersonnelScientificDegreeAndName;
use App\Models\PersonnelTakenCaptive;
use Illuminate\Database\Eloquent\Model;

class PersonnelRelationsService
{
    use NormalizesDropdownPayloads;

    /**
     * @param  array<string, mixed>  $payloads
     * @param  array<int, string>  $completedSteps
     */
    public function create(Personnel $personnel, array $payloads, array $completedSteps = []): void
    {
        $this->createRelatedData($personnel, 'idDocuments', PersonnelIdentityDocument::class, $payloads['document'] ?? [], $completedSteps);
        $this->createMultipleRelatedData($personnel, 'cards', PersonnelCard::class, $payloads['service_cards'] ?? []);
        $this->createMultipleRelatedData($personnel, 'passports', PersonnelPassports::class, $payloads['passports'] ?? []);
        $this->createRelatedData($personnel, 'education', PersonnelEducation::class, $payloads['education'] ?? [], $completedSteps);
        $this->createMultipleRelatedData($personnel, 'extraEducations', PersonnelExtraEducation::class, $payloads['extra_educations'] ?? []);
        $laborActivities = $this->prepareLaborActivities($payloads['labor_activities'] ?? [], $personnel);
        $this->createMultipleRelatedData($personnel, 'laborActivities', PersonnelLaborActivity::class, $laborActivities);
        $this->createMultipleRelatedData($personnel, 'ranks', PersonnelRank::class, $payloads['ranks'] ?? []);
        $this->createMultipleRelatedData($personnel, 'military', PersonnelMilitaryService::class, $payloads['military'] ?? []);
        $this->createMultipleRelatedData($personnel, 'injuries', PersonnelInjury::class, $payloads['injuries'] ?? []);
        $this->createMultipleRelatedData($personnel, 'captives', PersonnelTakenCaptive::class, $payloads['captivities'] ?? []);
        $this->createMultipleRelatedData($personnel, 'awards', PersonnelAward::class, $payloads['awards'] ?? []);
        $this->createMultipleRelatedData($personnel, 'punishments', PersonnelPunishment::class, $payloads['punishments'] ?? []);
        $this->createMultipleRelatedData($personnel, 'kinships', PersonnelKinship::class, $payloads['kinships'] ?? []);
        $this->createMultipleRelatedData($personnel, 'foreignLanguages', null, $payloads['languages'] ?? []);
        $this->createMultipleRelatedData($personnel, 'participations', PersonnelParticipationEvent::class, $payloads['events'] ?? []);
        $this->createMultipleRelatedData($personnel, 'degreeAndNames', PersonnelScientificDegreeAndName::class, $payloads['degrees'] ?? []);
        $this->createMultipleRelatedData($personnel, 'elections', PersonnelElectedElectoral::class, $payloads['elections'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $payloads
     */
    public function update(Personnel $personnel, array $payloads): void
    {
        $this->handleSingleAssociation($personnel, relation: 'document', data: $payloads['document'] ?? [], model: PersonnelIdentityDocument::class, differentRelationName: 'idDocuments');
        $this->handleAssociations($personnel, relation: 'cards', list: $payloads['service_cards'] ?? [], uniqueKeys: 'card_number', model: PersonnelCard::class);
        $this->handleAssociations($personnel, relation: 'passports', list: $payloads['passports'] ?? [], uniqueKeys: 'serial_number', model: PersonnelPassports::class);
        $this->handleSingleAssociation($personnel, relation: 'education', data: $payloads['education'] ?? [], model: PersonnelEducation::class);
        $this->handleAssociations($personnel, relation: 'extraEducations', list: $payloads['extra_educations'] ?? [], uniqueKeys: 'diplom_no', model: PersonnelExtraEducation::class);
        $laborActivities = $this->prepareLaborActivities($payloads['labor_activities'] ?? [], $personnel);
        $this->handleAssociations($personnel, relation: 'laborActivities', list: $laborActivities, uniqueKeys: 'join_date', model: PersonnelLaborActivity::class);
        $this->handleAssociations($personnel, relation: 'ranks', list: $payloads['ranks'] ?? [], uniqueKeys: 'given_date', model: PersonnelRank::class, tabelCheck: true);
        $this->handleAssociations($personnel, relation: 'military', list: $payloads['military'] ?? [], uniqueKeys: 'start_date', model: PersonnelMilitaryService::class, tabelCheck: true);
        $this->handleAssociations($personnel, relation: 'injuries', list: $payloads['injuries'] ?? [], uniqueKeys: ['description', 'date_time'], model: PersonnelInjury::class, tabelCheck: true);
        $this->handleAssociations($personnel, relation: 'captives', list: $payloads['captivities'] ?? [], uniqueKeys: 'taken_captive_date', model: PersonnelTakenCaptive::class, tabelCheck: true);
        $this->handleAssociations($personnel, relation: 'awards', list: $payloads['awards'] ?? [], uniqueKeys: ['award_id', 'given_date'], model: PersonnelAward::class, tabelCheck: true);
        $this->handleAssociations($personnel, relation: 'punishments', list: $payloads['punishments'] ?? [], uniqueKeys: ['punishment_id', 'given_date'], model: PersonnelPunishment::class, tabelCheck: true);
        $this->handleAssociations($personnel, relation: 'kinships', list: $payloads['kinships'] ?? [], uniqueKeys: 'kinship_id', model: PersonnelKinship::class);
        $this->handleAssociations($personnel, relation: 'foreignLanguages', list: $payloads['languages'] ?? [], uniqueKeys: 'language_id');
        $this->handleAssociations($personnel, relation: 'participations', list: $payloads['events'] ?? [], uniqueKeys: 'event_name', model: PersonnelParticipationEvent::class);
        $this->handleAssociations($personnel, relation: 'degreeAndNames', list: $payloads['degrees'] ?? [], uniqueKeys: 'degree_and_name_id', model: PersonnelScientificDegreeAndName::class);
        $this->handleAssociations($personnel, relation: 'elections', list: $payloads['elections'] ?? [], uniqueKeys: 'elected_date', model: PersonnelElectedElectoral::class);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $completedSteps
     */
    private function createRelatedData(Personnel $personnel, string $relationMethod, string $modelClass, array $data, array $completedSteps, ?string $differentRelationName = null): void
    {
        $relationName = $differentRelationName ?? $relationMethod;

        if (! in_array($relationMethod, $completedSteps, true) || ! $this->relationPayloadHasValues($data)) {
            return;
        }

        /** @var Model $instance */
        $instance = new $modelClass;
        $payload = $this->modifyArray($data, method_exists($instance, 'dateList') ? $instance->dateList() : []);
        $personnel->{$relationName}()->create($payload);
    }

    /**
     * @param  array<int, array<string, mixed>>  $dataList
     */
    private function createMultipleRelatedData(Personnel $personnel, string $relationMethod, ?string $modelClass, array $dataList, ?string $differentRelationName = null): void
    {
        $relationName = $differentRelationName ?? $relationMethod;

        if (empty($dataList)) {
            return;
        }

        foreach ($dataList as $data) {
            if (! $this->relationPayloadHasValues($data)) {
                continue;
            }

            /** @var Model|null $instance */
            $instance = $modelClass ? new $modelClass : null;
            $payload = $this->modifyArray($data, $instance && method_exists($instance, 'dateList') ? $instance->dateList() : []);
            $personnel->{$relationName}()->create($payload);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleSingleAssociation(Personnel $personnel, string $relation, array $data, string $model, ?string $differentRelationName = null): void
    {
        if (! $this->relationPayloadHasValues($data)) {
            return;
        }

        $relationName = $differentRelationName ?? $relation;
        /** @var Model $modelInstance */
        $modelInstance = new $model;
        $payload = $this->modifyArray($data, method_exists($modelInstance, 'dateList') ? $modelInstance->dateList() : []);

        $personnel->{$relationName}()->updateOrCreate(
            ['tabel_no' => $personnel->tabel_no],
            $payload
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $list
     * @param  array<int, string>|string  $uniqueKeys
     */
    private function handleAssociations(
        Personnel $personnel,
        string $relation,
        array $list,
        $uniqueKeys,
        ?string $model = null,
        bool $tabelCheck = false
    ): void {
        if (empty($list)) {
            $personnel->{$relation}()->delete();

            return;
        }

        $uniqueKeys = (array) $uniqueKeys;
        /** @var Model|null $modelInstance */
        $modelInstance = $model ? new $model : null;

        $updatedModelsId = [];
        $oldModelsId = $personnel->{$relation}->pluck('id')->all();

        foreach ($list as $item) {
            if (! $this->relationPayloadHasValues($item)) {
                continue;
            }

            $payload = $this->modifyArray($item, $modelInstance && method_exists($modelInstance, 'dateList') ? $modelInstance->dateList() : []);
            $queryKeys = array_intersect_key($payload, array_flip($uniqueKeys));

            if ($tabelCheck) {
                $queryKeys['tabel_no'] = $personnel->tabel_no;
            }

            $persisted = $personnel->{$relation}()->updateOrCreate($queryKeys, $payload);
            $updatedModelsId[] = $persisted->id;
        }

        $deletedIds = array_diff($oldModelsId, $updatedModelsId);

        if (empty($deletedIds)) {
            return;
        }

        $personnel->{$relation}()
            ->whereIn('id', $deletedIds)
            ->get()
            ->each
            ->delete();
    }

    /**
     * Normalize labor activities and sync current position/structure IDs onto personnel
     * when the entry comes from list selection.
     *
     * @param  array<int, array<string, mixed>>  $list
     * @return array<int, array<string, mixed>>
     */
    private function prepareLaborActivities(array $list, Personnel $personnel): array
    {
        $currentPositionId = null;
        $currentStructureId = null;
        $currentJoinDate = null;

        $normalized = collect($list)
            ->map(function (array $item) use (&$currentPositionId, &$currentStructureId, &$currentJoinDate) {
                $useLookup = ! empty($item['use_lookup']);
                $positionId = $item['position_id'] ?? null;
                $structureId = $item['structure_id'] ?? null;

                if (! empty($item['is_current']) && empty($item['leave_date'])) {
                    if (! empty($item['join_date'])) {
                        try {
                            $currentJoinDate = \Carbon\Carbon::parse($item['join_date'])->toDateString();
                        } catch (\Throwable $exception) {
                            $currentJoinDate = null;
                        }
                    }
                    if ($useLookup) {
                        $currentPositionId = $positionId ?? $currentPositionId;
                        $currentStructureId = $structureId ?? $currentStructureId;
                    }
                }

                unset($item['use_lookup'], $item['position_label'], $item['structure_label'], $item['position_id'], $item['structure_id']);

                return $item;
            })
            ->all();

        if ($currentPositionId && $personnel->position_id !== $currentPositionId) {
            $personnel->position_id = $currentPositionId;
        }
        if ($currentStructureId && $personnel->structure_id !== $currentStructureId) {
            $personnel->structure_id = $currentStructureId;
        }
        if ($currentJoinDate && $personnel->join_work_date !== $currentJoinDate) {
            $personnel->join_work_date = $currentJoinDate;
        }
        if ($currentPositionId || $currentStructureId || $currentJoinDate) {
            $personnel->save();
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function relationPayloadHasValues(?array $data): bool
    {
        if (is_null($data)) {
            return false;
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->payloadHasValues($value)) {
                    return true;
                }

                continue;
            }

            if ($this->isScalarValueFilled($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $data
     */
    private function payloadHasValues($data): bool
    {
        if (is_null($data)) {
            return false;
        }

        if (! is_array($data)) {
            return $this->isScalarValueFilled($data);
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->payloadHasValues($value)) {
                    return true;
                }

                continue;
            }

            if ($this->isScalarValueFilled($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $value
    */
    private function isScalarValueFilled($value): bool
    {
        if (is_bool($value)) {
            return $value === true;
        }

        return $value !== null && $value !== '';
    }
}
