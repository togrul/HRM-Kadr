<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PersonnelWizardStepBindingIsolationTest extends TestCase
{
    public function test_child_step_templates_do_not_bind_to_parent_personal_form_state(): void
    {
        $paths = [
            resource_path('views/includes/step2.blade.php'),
            resource_path('views/includes/step3.blade.php'),
            resource_path('views/includes/step4.blade.php'),
            resource_path('views/includes/step5.blade.php'),
            resource_path('views/includes/step6.blade.php'),
            resource_path('views/includes/step7.blade.php'),
            resource_path('views/includes/step8.blade.php'),
        ];

        foreach ($paths as $path) {
            $contents = File::get($path);

            $this->assertStringNotContainsString(
                'wire:model="personalForm.',
                $contents,
                "Unexpected parent personalForm wire:model binding found in {$path}"
            );

            $this->assertStringNotContainsString(
                'wire:model.live="personalForm.',
                $contents,
                "Unexpected parent personalForm wire:model.live binding found in {$path}"
            );

            $this->assertStringNotContainsString(
                'wire:model.defer="personalForm.',
                $contents,
                "Unexpected parent personalForm wire:model.defer binding found in {$path}"
            );
        }
    }
}
