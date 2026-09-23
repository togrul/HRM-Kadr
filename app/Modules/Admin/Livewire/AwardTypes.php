<?php

namespace App\Modules\Admin\Livewire;

use App\Models\AwardType;
use App\Modules\Admin\Support\ReferenceTypePanel;

class AwardTypes extends ReferenceTypePanel
{
    protected string $modelClass = AwardType::class;

    protected string $savedEvent = 'awardTypeUpdated';
}
