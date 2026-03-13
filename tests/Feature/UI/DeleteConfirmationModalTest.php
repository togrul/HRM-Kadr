<?php

namespace Tests\Feature\UI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\DeleteConfirmationHarness;
use Tests\TestCase;

class DeleteConfirmationModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_opens_the_modal_with_configured_copy_and_resets_after_confirmation(): void
    {
        Livewire::test(DeleteConfirmationHarness::class)
            ->call('requestDelete', 42)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSee('Delete training record?')
            ->assertSee('Record #42')
            ->assertSee('Remove now')
            ->call('runConfirmedDeletion')
            ->assertSet('deletedId', 42)
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('deleteConfirmation.action', null);
    }

    public function test_it_maps_positional_parameters_for_confirmed_actions(): void
    {
        Livewire::test(DeleteConfirmationHarness::class)
            ->call('requestDeleteByIndex', 77)
            ->call('runConfirmedDeletion')
            ->assertSet('deletedId', 77)
            ->assertSet('showDeleteConfirmation', false);
    }

    public function test_it_resets_dialog_state_when_closed_without_confirmation(): void
    {
        Livewire::test(DeleteConfirmationHarness::class)
            ->call('requestDelete', 15)
            ->call('closeDeleteConfirmation')
            ->assertSet('deletedId', null)
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('deleteConfirmation.action', null)
            ->assertSet('deleteConfirmation.message', null)
            ->assertSet('deleteConfirmation.parameters', []);
    }

    public function test_it_noops_when_confirmation_runs_without_registered_action(): void
    {
        Livewire::test(DeleteConfirmationHarness::class)
            ->call('runConfirmedDeletion')
            ->assertSet('deletedId', null)
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('deleteConfirmation.action', null);
    }
}
