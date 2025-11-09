<?php

namespace App\Livewire\Traits\RelationCruds;

trait RelationCrudTrait
{
    private function handleSingleAssociation($relation, $data, $model, $differentRelationName = null): void
    {
        $relationName = $differentRelationName ?? $relation;
        if (! $this->relationPayloadHasValues($data)) {
            return;
        }

        $modelInstance = new $model;
        $modifiedData = $this->modifyArray($data, $modelInstance->dateList());
        $this->personnelModelData->$relationName()->updateOrCreate(
            ['tabel_no' => $this->personnelModelData->tabel_no],
            $modifiedData
        );
    }

    private function handleAssociations($relation, $list, $uniqueKeys, $model = null, $tabelCheck = false): void
    {
        $uniqueKeys = (array) $uniqueKeys;
        $modelInstance = $model ? new $model : null;

        if (empty($list)) {
            $this->personnelModelData->$relation()->delete();
            return;
        }

        $updatedModelsId = [];
        $oldModelsId = $this->personnelModelData->$relation->pluck('id')->toArray();

        foreach ($list as $item) {
            if (! $this->relationPayloadHasValues($item)) {
                continue;
            }

            $modifiedData = $this->modifyArray($item, $modelInstance?->dateList() ?? []);
            $queryKeys = array_intersect_key($modifiedData, array_flip($uniqueKeys));
            if ($tabelCheck) {
                $queryKeys['tabel_no'] = $this->personnelModelData->tabel_no;
            }
            $data = $this->personnelModelData->$relation()->updateOrCreate($queryKeys, $modifiedData);
            $updatedModelsId[] = $data->id;
        }
        $deletedIds = array_diff($oldModelsId, $updatedModelsId);

        $modelsToDelete = $this->personnelModelData->$relation()->whereIn('id', $deletedIds)->get();

        foreach ($modelsToDelete as $model) {
            $model->delete(); // Triggers deleted event, logs it
        }
    }

    private function createRelatedData($personnel, $relationMethod, $modelClass, $data, $differentRelationName = null): void
    {
        $relationName = $differentRelationName ?? $relationMethod;
        if (in_array($relationMethod, $this->completedSteps) && $this->relationPayloadHasValues($data)) {
            $instance = new $modelClass;
            $data = $this->modifyArray($data, $instance->dateList() ?? []);
            $personnel->{$relationName}()->create($data);
        }
    }

    private function createMultipleRelatedData($personnel, $relationMethod, $modelClass, $dataList, $differentRelationName = null): void
    {
        $relationName = $differentRelationName ?? $relationMethod;
        if (! empty($dataList)) {
            foreach ($dataList as $data) {
                if (! $this->relationPayloadHasValues($data)) {
                    continue;
                }

                $instance = $modelClass ? new $modelClass : null;
                $data = $this->modifyArray($data, $instance ? $instance->dateList() : []);
                $personnel->{$relationName}()->create($data);
            }
        }
    }

    private function relationPayloadHasValues($data): bool
    {
        if (is_null($data)) {
            return false;
        }

        if (! is_array($data)) {
            return $this->isRelationScalarValueFilled($data);
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->payloadHasValues($value)) {
                    return true;
                }

                continue;
            }

            if ($this->isRelationScalarValueFilled($value)) {
                return true;
            }
        }

        return false;
    }

    private function isRelationScalarValueFilled($value): bool
    {
        if (is_bool($value)) {
            return $value === true;
        }

        return $value !== null && $value !== '';
    }
}
