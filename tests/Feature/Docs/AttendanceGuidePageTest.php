<?php

namespace Tests\Feature\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceGuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_docs_are_available_inside_common_docs_page(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get(route('docs.guide', ['focus' => 'attendance']))
            ->assertOk()
            ->assertSee('Davamiyyət modulu')
            ->assertSee('Davamiyyət istifadəçi bələdçisi')
            ->assertSee('Davamiyyət iş axını');
    }
}
