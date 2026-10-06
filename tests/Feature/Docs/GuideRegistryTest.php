<?php

namespace Tests\Feature\Docs;

use App\Support\Docs\GuideRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GuideRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_registered_guide_has_a_markdown_file_and_a_real_module_route(): void
    {
        $this->assertNotEmpty(GuideRegistry::modules());

        foreach (GuideRegistry::modules() as $key => $module) {
            $this->assertFileExists(GuideRegistry::markdownPath($module['markdown']), $key);

            if ($module['route'] !== null) {
                $this->assertTrue(Route::has($module['route']), "{$key}: {$module['route']}");
            }
        }
    }

    public function test_pages_resolve_to_their_module_guide(): void
    {
        $this->assertSame('attendance', GuideRegistry::forRoute('attendance'));
        $this->assertSame('orders', GuideRegistry::forRoute('orders'));
        $this->assertSame('candidates', GuideRegistry::forRoute('candidates.openings'));
        $this->assertSame('performance', GuideRegistry::forRoute('performance-evaluation.succession'));
        $this->assertNull(GuideRegistry::forRoute('docs.guide'));
        $this->assertNull(GuideRegistry::forRoute(null));
    }

    public function test_the_guide_sidebar_lists_each_modules_own_headings_and_every_module_loads(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $response = $this->get(route('docs.guide', ['focus' => 'attendance']))->assertOk();

        foreach (GuideRegistry::modules() as $key => $module) {
            $response->assertSee($module['label']);
            $response->assertSee('data-docs-link="'.$key.'-module"', false);

            $this->getJson(route('docs.section', ['module' => $key]))
                ->assertOk()
                ->assertJsonPath('module', $key)
                ->assertSee('id=\"'.$key.'-h-1\"', false);
        }

        $response->assertSee('data-docs-link="attendance-h-1"', false);
        $this->getJson(route('docs.section', ['module' => 'nope']))->assertNotFound();
    }
}
