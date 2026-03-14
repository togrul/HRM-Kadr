<?php

namespace Tests\Feature\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceGuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_guide_route_redirects_to_common_docs_page(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->get(route('attendance.user-guide'))
            ->assertOk()
            ->assertSee('Davamiyyət modulu')
            ->assertSee('Davamiyyət istifadəçi bələdçisi')
            ->assertSee('Davamiyyət iş axını');
    }
}
