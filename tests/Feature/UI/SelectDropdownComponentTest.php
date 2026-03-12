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
            ->assertSeeHtml('data-option-id="1"')
            ->assertSeeHtml('data-option-label="One"')
            ->assertSeeHtml('data-option-id="2"')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"');
    }

    public function test_it_refreshes_option_markup_when_the_available_options_change(): void
    {
        Livewire::test(SelectDropdownHarness::class)
            ->call('swapOptions')
            ->assertSeeHtml('data-option-id="archived"')
            ->assertSeeHtml('data-option-label="Archived"')
            ->assertSeeHtml('data-option-label="Second"')
            ->assertDontSeeHtml('data-option-label="One"');
    }
}
