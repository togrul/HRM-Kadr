<?php

namespace App\Modules\Admin\Support\Traits\Admin;

trait CallSwalTrait
{
    public function callSuccessSwal()
    {
        $this->dispatch('swal', [
            'title' => __('admin::common.alerts.success.title'),
            'text' => __('admin::common.alerts.success.text'),
            'icon' => 'success',
            'timer' => 2000,
            'timerProgressBar' => true,
        ]);
    }

    public function callWarningSwal()
    {
        $this->dispatch('swal', [
            'title' => __('admin::common.alerts.warning.title'),
            'text' => __('admin::common.alerts.warning.text'),
            'icon' => 'error',
            'timer' => 2000,
            'timerProgressBar' => true,
        ]);
    }

    public function callDeletePromptSwal()
    {
        $this->dispatch('delete-prompt', [
            'title' => __('admin::common.alerts.delete_prompt.title'),
            'text' => __('admin::common.alerts.delete_prompt.text'),
            'icon' => 'warning',
        ]);
    }
}
