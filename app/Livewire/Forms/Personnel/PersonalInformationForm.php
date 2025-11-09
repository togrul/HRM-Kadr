<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

/**
 * Encapsulates Step 1 (personal information) state for the personnel wizard.
 *
 * This form is intentionally self-contained so we can hydrate/reset/serialize
 * personal data without keeping the logic inside the Livewire component.
 * Wiring will happen in the next phases of the refactor.
 */
class PersonalInformationForm extends Form
{
    /**
     * Main personal information payload (maps to personnel table).
     */
    public array $personnel = [];

    /**
     * Extra boolean/info fields that are stored on the personnel record.
     */
    public array $personnelExtra = [];

    /**
     * Convenience flags / derived state.
     */
    public bool $hasDisability = false;

    /**
     * Cached avatar reference (will be managed by the parent component).
     */
    public ?string $avatarPath = null;

    public function resetForm(): void
    {
        $this->personnel = $this->defaultPersonnel();
        $this->personnelExtra = $this->defaultPersonnelExtra();
        $this->hasDisability = false;
        $this->avatarPath = null;
    }

    /**
     * Fill the form from an existing Personnel model.
     */
    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $loaded = $personnel->load([
            'nationality',
            'previousNationality',
            'educationDegree',
            'structure',
            'position',
            'workNorm',
            'disability',
            'socialOrigin',
        ]);

        $payload = $loaded->toArray();

        $this->personnel = array_replace_recursive(
            $this->personnel,
            Arr::only($payload, $this->personnelAttributes())
        );

        $this->personnelExtra = array_replace_recursive(
            $this->personnelExtra,
            Arr::only($payload, $this->personnelExtraAttributes())
        );

        $this->applyRelation($payload, 'nationality', 'title', 'nationality_id');
        $this->applyRelation($payload, 'previous_nationality', 'title', 'previous_nationality_id');
        $this->applyRelation($payload, 'education_degree', 'title_'.app()->getLocale(), 'education_degree_id');
        $this->applyRelation($payload, 'structure', 'name', 'structure_id');
        $this->applyRelation($payload, 'position', 'name', 'position_id');
        $this->applyRelation($payload, 'work_norm', 'name_'.app()->getLocale(), 'work_norm_id');
        $this->applyRelation(
            payload: $payload,
            relation: 'disability',
            labelField: 'name',
            target: 'disability_id',
            extraCallback: function (?array $relation) {
                $this->hasDisability = ! empty($relation);
                if ($relation && isset($relation['pivot']['given_date'])) {
                    $this->personnel['disability_given_date'] = $relation['pivot']['given_date'];
                }
            }
        );
        $this->applyRelation($payload, 'social_origin', 'name', 'social_origin_id');
    }

    /**
     * Transform the current form state into an array suitable for persistence.
     */
    public function toPayload(): array
    {
        return [
            'personnel'       => $this->personnel,
            'personnel_extra' => $this->personnelExtra,
            'avatar_path'     => $this->avatarPath,
        ];
    }

    /**
     * Default structure for the personnel array.
     */
    protected function defaultPersonnel(): array
    {
        return [
            'has_changed_initials'    => false,
            'has_changed_nationality' => false,
            'nationality_id'          => null,
            'previous_nationality_id' => null,
            'education_degree_id'     => null,
            'structure_id'            => null,
            'position_id'             => null,
            'work_norm_id'            => null,
            'disability_id'           => null,
            'social_origin_id'        => null,
        ];
    }

    /**
     * Default structure for the personnel_extra array.
     */
    protected function defaultPersonnelExtra(): array
    {
        return [
            'participation_in_war'   => null,
            'discrediting_information' => null,
        ];
    }

    /**
     * Helper to map relations into `{id, name}` arrays similar to legacy code.
     */
    protected function applyRelation(
        array $payload,
        string $relation,
        string $labelField,
        string $target,
        ?callable $extraCallback = null
    ): void {
        $entity = data_get($payload, $relation);
        if (! $entity) {
            $this->personnel[$target] = null;
            if (is_callable($extraCallback)) {
                $extraCallback(null);
            }

            return;
        }

        $this->personnel[$target] = data_get($entity, 'id');

        if (is_callable($extraCallback)) {
            $extraCallback($entity);
        }
    }

    /**
     * List of scalar attributes we copy from the Personnel model.
     */
    protected function personnelAttributes(): array
    {
        return [
            'tabel_no',
            'surname',
            'name',
            'patronymic',
            'photo',
            'has_changed_initials',
            'previous_surname',
            'previous_name',
            'previous_patronymic',
            'initials_changed_date',
            'initials_change_reason',
            'birthdate',
            'gender',
            'mobile',
            'phone',
            'email',
            'has_changed_nationality',
            'nationality_changed_date',
            'nationality_change_reason',
            'pin',
            'residental_address',
            'registered_address',
            'join_work_date',
            'leave_work_date',
            'extra_important_information',
            'computer_knowledge',
            'scientific_works_inventions',
            'referenced_by',
            'special_inspection_date',
            'special_inspection_result',
            'medical_inspection_date',
            'medical_inspection_result',
            'disability_given_date',
        ];
    }

    /**
     * List of auxiliary attributes maintained alongside the personnel payload.
     */
    protected function personnelExtraAttributes(): array
    {
        return [
            'participation_in_war',
            'discrediting_information',
        ];
    }

}
