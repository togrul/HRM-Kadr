<?php

namespace Tests\Feature\UI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SelectDropdownMorphStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_carries_a_stable_wire_key_so_livewire_patches_instead_of_replacing(): void
    {
        // The options panel lives in an x-teleport. Without a key on the root, Livewire's
        // morph replaces the element on any parent re-render, tearing the panel out and
        // resetting Alpine's isOpen — which closed the dropdown on every search keystroke.
        $first = $this->render(['searchModel' => 'citySearch', 'label' => 'Şəhər']);
        $again = $this->render(['searchModel' => 'citySearch', 'label' => 'Şəhər']);

        $this->assertSame($this->wireKey($first), $this->wireKey($again), 'the key must be stable across renders');
        $this->assertStringStartsWith('ui-select-', $this->wireKey($first));
    }

    public function test_two_dropdowns_on_one_page_do_not_share_a_key(): void
    {
        $city = $this->wireKey($this->render(['searchModel' => 'citySearch', 'label' => 'Şəhər']));
        $country = $this->wireKey($this->render(['searchModel' => 'countrySearch', 'label' => 'Ölkə']));

        $this->assertNotSame($city, $country);
    }

    public function test_a_caller_supplied_key_wins(): void
    {
        $html = $this->render(['label' => 'Şəhər', 'wire:key' => 'my-own-key']);

        $this->assertSame('my-own-key', $this->wireKey($html));
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function render(array $attributes): string
    {
        return Blade::render(
            '<x-ui.select-dropdown :attributes="$bag" />',
            ['bag' => new \Illuminate\View\ComponentAttributeBag($attributes)]
        );
    }

    private function wireKey(string $html): string
    {
        preg_match('/wire:key="([^"]+)"/', $html, $matches);

        return $matches[1] ?? '';
    }
}
