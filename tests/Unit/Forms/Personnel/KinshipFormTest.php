<?php

namespace Tests\Unit\Forms\Personnel;

use App\Livewire\Forms\Personnel\KinshipForm;
use Livewire\Component;
use Tests\TestCase;

class KinshipFormTest extends TestCase
{
    public function test_it_updates_existing_kinship_entry_in_edit_mode(): void
    {
        $component = new class extends Component
        {
            public function render()
            {
                return '';
            }
        };

        $form = new KinshipForm($component, 'kinshipForm');
        $form->resetForm();

        $form->kinshipList = [
            [
                'id' => 11,
                'row_key' => 'kinship-11',
                'kinship_id' => 21,
                'kinship_name' => 'Qardaş',
                'fullname' => 'Əli Məmmədov',
                'birthdate' => '1990-01-01',
                'birth_place' => 'Bakı',
                'company_name' => null,
                'position' => null,
                'registered_address' => 'A',
                'residental_address' => 'B',
                'birth_certificate_number' => null,
                'marriage_certificate_number' => null,
            ],
        ];

        $form->beginKinshipEdit('kinship-11');
        $form->kinship['fullname'] = 'Əli Məmmədov yenilənib';
        $form->saveKinshipEntry('Qardaş');

        $this->assertNull($form->editingKinshipKey);
        $this->assertSame('Əli Məmmədov yenilənib', $form->kinshipList[0]['fullname']);
        $this->assertSame(11, $form->kinshipList[0]['id']);
        $this->assertSame('kinship-11', $form->kinshipList[0]['row_key']);
    }
}
