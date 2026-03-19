<?php

namespace Tests\Feature\Admin;

use App\Models\LeaveType;
use App\Modules\Admin\Livewire\LeaveTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_attendance_code_to_compact_uppercase(): void
    {
        Livewire::test(LeaveTypes::class)
            ->call('openCrud')
            ->set('form.name', 'Saatlıq icazə')
            ->set('form.attendance_code', ' si - 1 ')
            ->set('form.max_days', 3)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('leave_types', [
            'name' => 'Saatlıq icazə',
            'attendance_code' => 'SI-1',
        ]);
    }

    public function test_it_rejects_duplicate_attendance_code_even_with_case_variants(): void
    {
        LeaveType::query()->create([
            'name' => 'İllik',
            'attendance_code' => 'ILL',
            'max_days' => 21,
            'requires_document' => false,
        ]);

        Livewire::test(LeaveTypes::class)
            ->call('openCrud')
            ->set('form.name', 'Yeni növ')
            ->set('form.attendance_code', ' ill ')
            ->set('form.max_days', 1)
            ->call('store')
            ->assertHasErrors(['form.attendance_code' => ['unique']]);
    }
}
