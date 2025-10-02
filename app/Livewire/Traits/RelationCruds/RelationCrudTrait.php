<?php

namespace App\Livewire\Traits\RelationCruds;

trait RelationCrudTrait
{
    private function handleSingleAssociation($relation, $data, $model, $differentRelationName = null): void
    {
        $relationName = $differentRelationName ?? $relation;
        if (in_array($relation, $this->completedSteps) || ! empty($data)) {
            $modelInstance = new $model;
            $modifiedData = $this->modifyArray($data, $modelInstance->dateList());
            $this->personnelModelData->$relationName()->updateOrCreate(
                ['tabel_no' => $this->personnelModelData->tabel_no],
                $modifiedData
            );
        }
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
        if (in_array($relationMethod, $this->completedSteps) && ! empty($data)) {
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
                $instance = $modelClass ? new $modelClass : null;
                $data = $this->modifyArray($data, $instance ? $instance->dateList() : []);
                $personnel->{$relationName}()->create($data);
            }
        }
    }
}
