<?php

namespace Tests\Feature\UI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\SelectDropdownHarness;
use Tests\TestCase;

class SelectDropdownComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_dom_contract_for_options_and_search_input(): void
    {
        Livewire::test(SelectDropdownHarness::class)
            ->assertSee('State')
            ->assertSee('Search states')
            ->assertSeeHtml('data-option-id="1"')
            ->assertSeeHtml('data-option-label="One"')
            ->assertSeeHtml('data-option-id="2"')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
            ->assertSeeHtml('x-model.live.debounce.150ms="localSearch"')
            ->assertSeeHtml('x-show="matchesSearch($el.dataset.optionLabel)"')
            ->assertSee('status-group');
    }

    public function test_it_refreshes_option_markup_when_the_available_options_change(): void
    {
        Livewire::test(SelectDropdownHarness::class)
            ->call('swapOptions')
            ->assertSet('selected', 'archived')
            ->assertSeeHtml('data-option-id="archived"')
            ->assertSeeHtml('data-option-label="Archived"')
            ->assertSeeHtml('data-option-label="Second"')
            ->assertDontSeeHtml('data-option-label="One"');
    }

    public function test_it_bootstraps_cached_selected_label_when_option_is_not_in_current_payload(): void
    {
        Livewire::test(SelectDropdownHarness::class)
            ->call('detachSelectedOption')
            ->assertSet('selected', 'archived')
            ->assertSee('Archived from cache')
            ->assertDontSeeHtml('data-option-id="archived"');
    }
}
