<?php

namespace Tests\Feature\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingPerformanceGuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_common_hr_modules_guide_page(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get(route('docs.guide'))
            ->assertOk()
            ->assertSee('HR modullarının ortaq istifadə bələdçisi')
            ->assertSee('Təlim ehtiyacı')
            ->assertSee('Performans qiymətləndirməsi')
            ->assertSee('Davamiyyət')
            ->assertSee('Əmrlər');
    }

    public function test_focus_parameter_loads_requested_module_on_initial_render(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get(route('docs.guide', ['focus' => 'training']))
            ->assertOk()
            ->assertSee('Təlim ehtiyacı istifadəçi bələdçisi')
            ->assertDontSee('Performans qiymətləndirmə istifadəçi bələdçisi');
    }
}
