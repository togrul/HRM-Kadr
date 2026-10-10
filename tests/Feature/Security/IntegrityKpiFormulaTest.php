<?php

use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiFormula;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\KpiLibraryWorkspace;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * L3: formula uzunluğu və iç-içəlik dərinliyi təhlil başlamazdan əvvəl məhdudlaşdırılır.
 */

it('refuses an over-long formula before tokenizing it', function (): void {
    expect(fn () => app(KpiFormula::class)->references(str_repeat('1+', 1000).'1'))
        ->toThrow(InvalidArgumentException::class, __('performance_evaluation::kpi.formula.errors.too_long', ['max' => KpiFormula::MAX_LENGTH]));
});

it('refuses formulas nested deeper than the limit', function (): void {
    $formula = app(KpiFormula::class);
    $tooDeep = __('performance_evaluation::kpi.formula.errors.too_deep', ['max' => KpiFormula::MAX_DEPTH]);

    expect(fn () => $formula->evaluate(str_repeat('(', 200).'1'.str_repeat(')', 200), []))->toThrow(InvalidArgumentException::class, $tooDeep)
        ->and(fn () => $formula->evaluate(str_repeat('ABS(', 100).'1'.str_repeat(')', 100), []))->toThrow(InvalidArgumentException::class, $tooDeep)
        ->and(fn () => $formula->evaluate(str_repeat('-', 500).'1', []))->toThrow(InvalidArgumentException::class, $tooDeep)
        ->and($formula->evaluate(str_repeat('(', 10).'2*3'.str_repeat(')', 10), []))->toBe(6.0)
        ->and($formula->evaluate('IF({A} > 50, MAX({A}, 70), ROUND({A} / 3, 1))', ['A' => 100.0]))->toBe(100.0);
});

it('validates the length in the workspace before testing the formula', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'), Permission::findOrCreate('show-performance-evaluation', 'web'));
    $this->actingAs($user);

    Livewire::test(KpiLibraryWorkspace::class)
        ->set('kpiForm.formula', str_repeat('1+', 1500).'1')
        ->call('testFormula')
        ->assertHasErrors(['kpiForm.formula' => 'max']);
});
