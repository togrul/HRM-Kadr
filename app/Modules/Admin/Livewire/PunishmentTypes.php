<?php

namespace App\Modules\Admin\Livewire;

use App\Models\PunishmentType;
use App\Modules\Admin\Support\ReferenceTypePanel;

class PunishmentTypes extends ReferenceTypePanel
{
    protected string $modelClass = PunishmentType::class;

    protected string $savedEvent = 'punishmentTypeUpdated';
}
