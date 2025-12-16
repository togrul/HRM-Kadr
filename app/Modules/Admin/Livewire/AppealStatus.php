<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Models\AppealStatus as AppealStatusAlias;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
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
            'form.id' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('appeal_statuses', 'id')
                    ->where(fn ($q) => $q->where('locale', $this->selectedLocale))
                    ->ignore($this->model?->id, 'id'),
            ],
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

    private function findByIdAndLocale(int $id): ?AppealStatusAlias
    {
        return AppealStatusAlias::where('id', $id)
            ->where('locale', $this->selectedLocale)
            ->first();
    }

    public function openCrud(?int $id = null): void
    {
        if ($id) {
            $this->model = $this->findByIdAndLocale($id);

            if (!$this->model) {
                return;
            }

            $this->form = $this->model->toArray();
        } else {
            $this->model = null;
            $this->form = [];
        }

        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = $this->findByIdAndLocale($id);

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

        DB::transaction(function () use ($data){
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $this->model
                ? AppealStatusAlias::where([
                ['id', '=', $this->model->id],
                ['locale', '=', $this->selectedLocale],
            ])->update([
                'id' => $this->form['id'],
                'name' => $this->form['name'],
            ])
                : AppealStatusAlias::create($data);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });

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
        $_appeal_statuses = AppealStatusAlias::where('locale', $this->selectedLocale)->get();

        return view('admin::livewire.admin.appeal-status', compact('_appeal_statuses'));
    }
}
