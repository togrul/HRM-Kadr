<?php

namespace App\Modules\Admin\Livewire;

use App\Models\SocialOrigin;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['socialOriginUpdated', 'deleted'])]
class SocialOrigins extends ReferenceCrudComponent
{
    protected string $modelClass = SocialOrigin::class;

    protected string $savedEvent = 'socialOriginUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_social_origin');
    }
}
