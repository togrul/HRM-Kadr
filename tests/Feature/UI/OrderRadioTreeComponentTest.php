<?php

namespace Tests\Feature\UI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\OrderRadioTreeHarness;
use Tests\TestCase;

class OrderRadioTreeComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_input_radio_tree_renders_selected_structure_label_and_click_payload(): void
    {
        Livewire::test(OrderRadioTreeHarness::class)
            ->assertSee('10-cu idarənin 1-ci şöbəsinin')
            ->assertSee('İdarə seç')
            ->assertSee('setStructure', false)
            ->assertSee('structure_id', false)
            ->assertSee('coded', false)
            ->assertSee('1-ci şöbə');
    }
}
