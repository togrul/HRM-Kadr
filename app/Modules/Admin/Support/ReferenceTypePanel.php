<?php

namespace App\Modules\Admin\Support;

use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Child panel that adds/edits/deletes one "type" row (id + name) from inside its parent
 * list (Awards → award types, Punishments → punishment types). The parent mounts it with
 * `:model="$id"` and closes it on `close-child`.
 */
abstract class ReferenceTypePanel extends Component
{
    use CallSwalTrait;

    /** @var class-string<Model> */
    protected string $modelClass;

    /** Dispatched after save/delete so the parent list refreshes. */
    protected string $savedEvent;

    public array $childForm = [];

    public $model;

    public function rules(): array
    {
        return [
            'childForm.id' => [
                'required',
                'integer',
                'min:1',
                Rule::unique((new $this->modelClass)->getTable(), 'id')->ignore($this->model?->getKey()),
            ],
            'childForm.name' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'childForm.id' => __('admin::references.fields.id'),
            'childForm.name' => __('admin::references.fields.name'),
        ];
    }

    public function mount(): void
    {
        if ($this->model) {
            $this->model = $this->modelClass::findOrFail($this->model);
            $this->childForm = $this->model->toArray();
        }
    }

    public function store(): void
    {
        Gate::authorize('access-admin');

        $this->validate();

        $this->model
            ? $this->model->update($this->childForm)
            : $this->modelClass::create($this->childForm);

        $this->callSuccessSwal();

        $this->dispatch($this->savedEvent);
        $this->dispatch('close-child');
    }

    public function deleteModel(): void
    {
        if ($this->model) {
            $this->callDeletePromptSwal();
        }
    }

    public function delete(): void
    {
        Gate::authorize('access-admin');

        if ($this->model) {
            $this->model->delete();
            $this->dispatch($this->savedEvent);
            $this->callDeletedSwal();
            $this->dispatch('close-child');
        }
    }

    public function render(): View
    {
        return view('admin::livewire.admin.reference-type-panel');
    }
}
