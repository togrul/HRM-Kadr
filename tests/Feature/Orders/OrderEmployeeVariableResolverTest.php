<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Services\Orders\Variables\OrderEmployeeVariableResolver;
use App\Services\Orders\Variables\OrderVariableRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderEmployeeVariableResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_employee_variables_with_correct_declension(): void
    {
        $structure = Structure::query()->create([
            'name' => 'Mərkəzi Qida Məhsulları anbarı',
            'shortname' => 'MQM anbarı',
        ]);
        $position = Position::query()->create(['name' => 'sürücü-ekspeditor']);

        $personnel = $this->makePersonnel($structure->id, $position->id);

        $vars = app(OrderEmployeeVariableResolver::class)->resolve($personnel->fresh(['position', 'structure']));

        // Nominative + suffix
        $this->assertSame('Bayramov Ruslan Bəxtiyar', $vars['employee.full_name']);
        $this->assertSame('Bayramov Ruslan Bəxtiyar oğlu', $vars['employee.full_name_with_suffix']);
        // Grammatical cases (the keystone)
        $this->assertSame('Bayramov Ruslan Bəxtiyar oğluna', $vars['employee.full_name_dative']);
        $this->assertSame('Bayramov Ruslan Bəxtiyar oğlunun', $vars['employee.full_name_genitive']);
        // Initials
        $this->assertSame('R.B.Bayramov', $vars['employee.initials']);
        $this->assertSame('R.B.Bayramovun', $vars['employee.initials_genitive']);
        // Position / structure (possessive-form cases)
        $this->assertSame('sürücü-ekspeditor', $vars['employee.position']);
        $this->assertSame('Mərkəzi Qida Məhsulları anbarı', $vars['employee.structure']);
        $this->assertSame('Mərkəzi Qida Məhsulları anbarının', $vars['employee.structure_genitive']);
        $this->assertSame('Mərkəzi Qida Məhsulları anbarına', $vars['employee.structure_dative']);
        $this->assertSame('oğlu', $vars['employee.gender_suffix']);
    }

    public function test_female_personnel_uses_qizi_suffix(): void
    {
        $structure = Structure::query()->create(['name' => 'Logistika şöbəsi', 'shortname' => 'Logistika']);
        $position = Position::query()->create(['name' => 'mütəxəssis']);

        $vars = app(OrderEmployeeVariableResolver::class)->resolve(
            $this->makePersonnel($structure->id, $position->id, gender: 2, surname: 'Həsənova', name: 'Ləman', patronymic: 'Asif')->fresh()
        );

        $this->assertSame('Həsənova Ləman Asif qızı', $vars['employee.full_name_with_suffix']);
        $this->assertSame('Həsənova Ləman Asif qızının', $vars['employee.full_name_genitive']);
    }

    public function test_null_personnel_resolves_to_empty(): void
    {
        $this->assertSame([], app(OrderEmployeeVariableResolver::class)->resolve(null));
    }

    public function test_registry_lists_resolvable_variables_and_field_keys(): void
    {
        $registry = app(OrderVariableRegistry::class);

        $this->assertContains('employee.full_name_dative', $registry->keys());
        $this->assertContains('system.order_number', $registry->keys());
        $this->assertTrue($registry->isResolvable('employee.structure_genitive'));
        $this->assertFalse($registry->isResolvable('field.start_date'));
        // author-declared field keys are resolvable for that template
        $this->assertTrue($registry->isResolvable('field.start_date', ['field.start_date']));
        $this->assertArrayHasKey('İşçi', $registry->grouped());
        $this->assertArrayHasKey('Sistem', $registry->grouped());
    }

    private function makePersonnel(
        ?int $structureId,
        ?int $positionId,
        int $gender = 1,
        string $surname = 'Bayramov',
        string $name = 'Ruslan',
        string $patronymic = 'Bəxtiyar',
    ): Personnel {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname,
            'name' => $name,
            'patronymic' => $patronymic,
            'birthdate' => '1990-01-01',
            'gender' => $gender,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $positionId,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
