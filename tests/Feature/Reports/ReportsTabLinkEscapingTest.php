<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Tab links carry the period in their query string; escaping it twice (`&amp;amp;`) turned
 * `year`/`month` into `amp;year`/`amp;month`, so switching tabs dropped the period.
 */
class ReportsTabLinkEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_tab_links_keep_the_period_in_a_single_escaped_query_string(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-reports', 'web');
        $user->givePermissionTo('show-reports');

        $html = $this->actingAs($user)
            ->get(route('reports', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('&amp;amp;', $html);

        foreach (['overview', 'standard', 'dynamic', 'comparisons'] as $tab) {
            $this->assertStringContainsString('reports?tab='.$tab.'&amp;year=2026&amp;month=10"', $html);
        }
    }

    public function test_filter_item_escapes_a_bound_href_exactly_once(): void
    {
        $html = Blade::render(
            '<x-filter.item :href="$href" :active="false">Tab</x-filter.item>',
            ['href' => 'https://hrm.test/reports?tab=standard&year=2026&month=10']
        );

        $this->assertStringContainsString('href="https://hrm.test/reports?tab=standard&amp;year=2026&amp;month=10"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function test_action_button_aria_label_is_not_escaped_twice(): void
    {
        $html = Blade::render(
            '<x-action-button :aria-label="$label">x</x-action-button>',
            ['label' => 'Tom & Jerry']
        );

        $this->assertStringContainsString('aria-label="Tom &amp; Jerry"', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }
}
