<?php

namespace App\Modules\Admin\Support;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * A flat admin reference list (no filters, no pagination, no children) edited inline
 * above its table. Concrete components only declare the model, the save event, the
 * "add" label and — when they differ from `id` + `name` — their fields and columns.
 * Everything else, including the view, is shared.
 *
 * Field:  name => ['label' => string, 'rules' => mixed (optional), 'type' => 'text'|'number'|'checkbox']
 * Column: ['label' => string, 'attr' => string, 'class' => ?string, 'unit' => ?string]
 *         ['label' => string, 'lines' => [prefix => attr]]  stacked "prefix - value", blanks hidden
 *         ['label' => string, 'check' => attr]              boolean tick
 */
abstract class ReferenceCrudComponent extends Component
{
    use AdminCrudTrait;
    use CallSwalTrait;

    /** @var class-string<Model> */
    protected string $modelClass;

    /** Dispatched after every save; concrete classes listen to it via #[On]. */
    protected string $savedEvent;

    abstract protected function addLabel(): string;

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function fields(): array
    {
        return [
            'id' => $this->idField(),
            'name' => ['label' => __('admin::references.fields.name'), 'rules' => 'required|string|min:2'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function columns(): array
    {
        return collect($this->fields())
            ->map(fn (array $field, string $attr): array => ['label' => $field['label'], 'attr' => $attr])
            ->values()
            ->all();
    }

    protected function saveLabel(): string
    {
        return __('admin::references.actions.save');
    }

    protected function actionsLabel(): string
    {
        return __('admin::references.table.action');
    }

    /**
     * Manually entered primary key, unique within the model's table.
     *
     * @return array<string, mixed>
     */
    protected function idField(?string $label = null): array
    {
        return [
            'label' => $label ?? __('admin::references.fields.id'),
            'type' => 'number',
            'rules' => [
                'required',
                'integer',
                'min:1',
                Rule::unique((new $this->modelClass)->getTable(), 'id')->ignore($this->model?->getKey()),
            ],
        ];
    }

    /**
     * `{prefix}_az` (required) plus optional `{prefix}_en` / `{prefix}_ru` inputs.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function localeFields(string $prefix, string $label): array
    {
        return [
            "{$prefix}_az" => ['label' => $label, 'rules' => 'required|string|min:2'],
            "{$prefix}_en" => ['label' => "{$label} (EN)"],
            "{$prefix}_ru" => ['label' => "{$label} (RU)"],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function localeColumn(string $prefix, string $label): array
    {
        return ['label' => $label, 'lines' => ['AZ' => "{$prefix}_az", 'EN' => "{$prefix}_en", 'RU' => "{$prefix}_ru"]];
    }

    public function rules(): array
    {
        return collect($this->fields())
            ->filter(fn (array $field): bool => isset($field['rules']))
            ->mapWithKeys(fn (array $field, string $name): array => ["form.{$name}" => $field['rules']])
            ->all();
    }

    protected function validationAttributes(): array
    {
        return collect($this->fields())
            ->mapWithKeys(fn (array $field, string $name): array => ["form.{$name}" => $field['label']])
            ->all();
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id ? $this->modelClass::find($id) : null;
        $this->form = $this->castCheckboxes($this->model ? $this->model->toArray() : []);
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id && ($this->model = $this->modelClass::find($id))) {
            $this->callDeletePromptSwal();
        }
    }

    public function store(): void
    {
        Gate::authorize('access-admin');

        $this->validate();

        $data = $this->castCheckboxes(Arr::only($this->form, array_keys($this->fields())));

        $this->model
            ? $this->model->update($data)
            : $this->modelClass::create($data);

        $this->callSuccessSwal();

        $this->dispatch($this->savedEvent);
        $this->closeCrud();
    }

    public function render(): View
    {
        return view('admin::livewire.admin.reference-crud', [
            'items' => $this->modelClass::all(),
            'fields' => $this->fields(),
            'columns' => $this->columns(),
            'addLabel' => $this->addLabel(),
            'saveLabel' => $this->saveLabel(),
            'actionsLabel' => $this->actionsLabel(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function castCheckboxes(array $data): array
    {
        foreach ($this->fields() as $name => $field) {
            if (($field['type'] ?? null) === 'checkbox') {
                $data[$name] = (bool) ($data[$name] ?? false);
            }
        }

        return $data;
    }
}
