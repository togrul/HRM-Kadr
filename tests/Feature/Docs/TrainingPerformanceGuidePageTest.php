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
            ->get(route('training-performance.user-guide'))
            ->assertOk()
            ->assertSee('HR modullarının ortaq istifadə bələdçisi')
            ->assertSee('Təlim ehtiyacı istifadəçi bələdçisi')
            ->assertSee('Performans qiymətləndirmə istifadəçi bələdçisi')
            ->assertSee('Davamiyyət istifadəçi bələdçisi');
    }
}
