<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\AppealStatus as AppealStatusAlias;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['appealStatusUpdated', 'deleted'])]
class AppealStatus extends Component
{
    use AuthorizesRequests;
    use AdminCrudTrait;
    use CallSwalTrait;

    public string $selectedLocale;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1',
            'form.name' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? AppealStatusAlias::where('id', $id)
                ->where('locale', $this->selectedLocale)
                ->first()
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = AppealStatusAlias::where('id', $id)
                ->where('locale', $this->selectedLocale)
                ->first();

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function setLocale(string $lang): void
    {
        $this->selectedLocale = $lang;
        $this->closeCrud();
    }

    public function store(): void
    {
        $this->validate();

        $data = array_merge($this->form, ['locale' => $this->selectedLocale]);
        $this->model
            ? $this->model->where('locale', $this->selectedLocale)->update([
                    'name' => $this->form['name']
                ])
            : AppealStatusAlias::create($data);

        $this->callSuccessSwal();

        $this->dispatch('appealStatusUpdated');
        $this->closeCrud();
    }

    public function mount()
    {
        $this->selectedLocale = config('app.locale');
    }

    public function render()
    {
        $_appeal_statuses = AppealStatusAlias::query()
            ->where('locale', '=', $this->selectedLocale)
            ->get();

        return view('livewire.admin.appeal-status', compact('_appeal_statuses'));
    }
}
