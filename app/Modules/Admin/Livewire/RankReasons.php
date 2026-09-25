<?php

namespace App\Modules\Admin\Livewire;

use App\Models\RankReason;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['rankReasonUpdated', 'deleted'])]
class RankReasons extends ReferenceCrudComponent
{
    protected string $modelClass = RankReason::class;

    protected string $savedEvent = 'rankReasonUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_reason');
    }
}
