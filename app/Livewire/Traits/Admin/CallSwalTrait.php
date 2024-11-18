<?php

namespace App\Livewire\Traits\Admin;

trait CallSwalTrait
{
    public function callSuccessSwal()
    {
        $this->dispatch('swal', [
            'title' => __('Saved!'),
            'text' => __('Data was updated successfully!'),
            'icon' => 'success',
            'timer' => 2000,
            'timerProgressBar' => true,
        ]);
    }

    public function callWarningSwal()
    {
        $this->dispatch('swal', [
            'title' => __('Warning!'),
            'text' => __('Data already exist!'),
            'icon' => 'error',
            'timer' => 2000,
            'timerProgressBar' => true,
        ]);
    }

    public function callDeletePromptSwal()
    {
        $this->dispatch('delete-prompt', [
            'title' => __('Are you sure?'),
            'text' => __('This action cannot be undone!'),
            'icon' => 'warning',
        ]);
    }
}
