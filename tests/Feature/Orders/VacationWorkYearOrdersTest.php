<?php

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Models\VacationBalanceEntry;
use App\Modules\Orders\Application\Document\DocxPlaceholderParser;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderIssueOutcome;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Infrastructure\Document\VacationOrderTemplateUpgrader;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Əmr axını iş ili uçotu ilə: məzuniyyət əmri adı çəkilən iş ilindən (sonra ən köhnədən) çıxır,
 * birinci il 6 aydan sonra açılır, istifadə olunmamış məzuniyyətin kompensasiyası yalnız əmək
 * müqaviləsinə xitamda (ƏM m.144.2).
 */

beforeEach(function (): void {
    Storage::fake('local');
    Carbon::setTestNow('2026-10-09 10:00:00');
    $this->actingAs(User::factory()->create());
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '']);

    foreach (['emek_mezuniyyeti', 'istifade_olunmamis_mezuniyyet_kompensasiyasi', 'xitam'] as $code) {
        $this->artisan('orders:seed-word-templates', ['--only' => $code])->assertSuccessful();
    }
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function vwPersonnel(string $join = '2024-03-15'): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'VW'.Str::upper(Str::random(6)),
        'surname' => 'Həsənov',
        'name' => 'Kamran',
        'patronymic' => 'Elçin',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'email' => Str::lower(Str::random(8)).'@example.com',
        'mobile' => '994501112233',
        'nationality_id' => 1,
        'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        'residental_address' => 'Main st',
        'education_degree_id' => 1,
        'work_norm_id' => 1,
        'structure_id' => $structure->id,
        'position_id' => $position->id,
        'join_work_date' => $join,
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

function vwTemplate(string $code): OrderWordTemplate
{
    return OrderWordTemplate::query()->where('code', $code)->firstOrFail();
}

/**
 * @param  array<string, string>  $fieldsByLabel
 */
function vwIssue(string $code, Personnel $personnel, array $fieldsByLabel, string $number): OrderIssueOutcome
{
    $template = vwTemplate($code);
    $fields = [];
    foreach ($fieldsByLabel as $label => $value) {
        $fields[(string) collect($template->manualFields())->firstWhere('label', $label)['key']] = $value;
    }

    return app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
        $code, $personnel->id, null, null, null, $fields, $number, '09.10.2026', 'Bakı şəhəri',
    ), false);
}

function vwApprove(string $number): OrderLog
{
    $order = OrderLog::query()->where('order_no', $number)->sole();
    app(OrderStatusTransitionService::class)->approve($order);

    return $order->fresh();
}

$annual = fn (string $workYear, string $days, string $start, string $end, string $return): array => [
    'İş ili' => $workYear, 'Gün sayı' => $days, 'Başlama tarixi' => $start, 'Bitmə tarixi' => $end,
    'İşə başlama tarixi' => $return, 'Əsas mətni' => 'ərizə',
];

it('seeds the annual leave work-year field on the work_year role', function (): void {
    expect(collect(vwTemplate('emek_mezuniyyeti')->variables)->firstWhere('label', 'İş ili')['effect_role'])->toBe('work_year');
});

it('deducts an annual leave order from the work year it names and gives it back on revert', function () use ($annual): void {
    $personnel = vwPersonnel();

    $outcome = vwIssue('emek_mezuniyyeti', $personnel, $annual('2025-03-15', '10', '2026-10-12', '2026-10-21', '2026-10-22'), '1-M');
    expect($outcome->isSaved())->toBeTrue($outcome->message ?? '');
    $order = vwApprove('1-M');

    $years = collect(app(VacationBalanceService::class)->balanceOn($personnel->fresh(), CarbonImmutable::parse('2026-10-12'))['work_years'])->pluck('remaining', 'sequence')->all();
    expect($years)->toBe([1 => 21, 2 => 11, 3 => 21])
        ->and(VacationBalanceEntry::query()->where('source', 'order:'.$order->id)->sum('days'))->toBe(-10);

    app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib');

    expect(VacationBalanceEntry::query()->count())->toBe(0);
});

it('blocks a leave in the first six months and shows when it opens', function () use ($annual): void {
    $personnel = vwPersonnel('2026-06-01');

    $outcome = vwIssue('emek_mezuniyyeti', $personnel, $annual('2026-06-01', '5', '2026-10-12', '2026-10-16', '2026-10-17'), '2-M');

    expect($outcome->isSaved())->toBeFalse()
        ->and(OrderLog::query()->where('order_no', '2-M')->exists())->toBeFalse();

    $template = vwTemplate('emek_mezuniyyeti');
    $start = collect($template->manualFields())->firstWhere('label', 'Başlama tarixi')['key'];
    $balance = app(OrderCompositionIssuer::class)->vacationBalance($template, new OrderComposition(
        'emek_mezuniyyeti', $personnel->id, null, null, null, [$start => '2026-10-12'], '', '09.10.2026', 'Bakı şəhəri',
    ), persist: false);

    expect($balance['remaining'])->toBe(0)
        ->and($balance['next_available_from'])->toBe('2026-12-01')
        ->and($balance['work_years'][0]['available'])->toBeFalse();
});

it('refuses unused-leave compensation while the employee is still employed', function (): void {
    $personnel = vwPersonnel();

    $outcome = vwIssue('istifade_olunmamis_mezuniyyet_kompensasiyasi', $personnel, ['İş ili' => '2024-03-15', 'Gün sayı' => '5', 'Əsas mətni' => 'xitam'], '3-K');

    expect($outcome->isSaved())->toBeFalse()
        ->and($outcome->message)->toBe(__('orders::order_composer.vacation.compensation_requires_termination'));
});

it('compensates unused leave with the termination: issued with a termination on file, approved after it', function (): void {
    $personnel = vwPersonnel();

    expect(vwIssue('xitam', $personnel, ['Maddə' => '68-ci maddəsinə', 'Səbəb' => 'öz arzusu ilə', 'Xitam tarixi' => '2026-10-20', 'Əsas mətni' => 'ərizə'], '4-X')->isSaved())->toBeTrue();

    // More than the unused days of all work years (3 × 21) is refused.
    expect(vwIssue('istifade_olunmamis_mezuniyyet_kompensasiyasi', $personnel, ['İş ili' => '2024-03-15', 'Gün sayı' => '64', 'Əsas mətni' => 'xitam'], '5-K')->isSaved())->toBeFalse();

    expect(vwIssue('istifade_olunmamis_mezuniyyet_kompensasiyasi', $personnel, ['İş ili' => '2024-03-15', 'Gün sayı' => '30', 'Əsas mətni' => 'xitam'], '5-K')->isSaved())->toBeTrue();

    // The termination is not approved yet: the pay-out has no legal ground.
    expect(fn () => vwApprove('5-K'))->toThrow(DomainException::class);

    vwApprove('4-X');
    $order = vwApprove('5-K');

    $years = collect(app(VacationBalanceService::class)->balanceOn($personnel->fresh(), CarbonImmutable::parse('2026-10-20'))['work_years'])->pluck('remaining', 'sequence')->all();
    expect($years)->toBe([1 => 0, 2 => 12, 3 => 21])
        ->and((int) VacationBalanceEntry::query()->where('kind', 'compensation')->sum('days'))->toBe(-30);

    app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib');
    expect(VacationBalanceEntry::query()->where('kind', 'compensation')->count())->toBe(0);
});

it('allows compensation during employment when the organisation enables it', function (): void {
    Setting::query()->updateOrCreate(['name' => VacationSettings::COMPENSATION_WITHOUT_TERMINATION], ['value' => '1', 'type' => 'string']);
    $personnel = vwPersonnel();

    expect(vwIssue('istifade_olunmamis_mezuniyyet_kompensasiyasi', $personnel, ['İş ili' => '2024-03-15', 'Gün sayı' => '5', 'Əsas mətni' => 'ərizə'], '6-K')->isSaved())->toBeTrue();
    vwApprove('6-K');

    expect((int) VacationBalanceEntry::query()->where('kind', 'compensation')->sum('days'))->toBe(-5);
});

it('words the compensation order for the termination and upgrades an unedited stored template', function (): void {
    $template = vwTemplate('istifade_olunmamis_mezuniyyet_kompensasiyasi');
    $parser = app(DocxPlaceholderParser::class);
    $text = fn (): string => implode("\n", $parser->paragraphs(Storage::disk('local')->path($template->docx_path)));

    expect($text())->toContain('144-cü maddəsinin 2-ci hissəsini');

    // An install still on the previous seed: rewrite it back to the old wording first.
    $path = Storage::disk('local')->path($template->docx_path);
    $tmp = tempnam(sys_get_temp_dir(), 'vw').'.docx';
    $parser->rewriteLiterals($path, array_flip(VacationOrderTemplateUpgrader::COMPENSATION_LITERALS), $tmp);
    copy($tmp, $path);
    @unlink($tmp);
    $annual = vwTemplate('emek_mezuniyyeti');
    $annual->forceFill(['variables' => collect($annual->variables)->map(fn (array $v): array => [...$v, 'effect_role' => $v['effect_role'] === 'work_year' ? null : $v['effect_role']])->all()])->save();

    expect($text())->toContain('ərizəsini nəzərə alaraq');

    $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_09_161000_upgrade_vacation_order_templates_for_work_years.php');
    $migration->up();

    expect($text())->toContain('əmək müqaviləsinə xitam verilməsi ilə əlaqədar')
        ->and($text())->toContain('iş ilindən başlayaraq')
        ->and(collect(vwTemplate('emek_mezuniyyeti')->variables)->firstWhere('label', 'İş ili')['effect_role'])->toBe('work_year')
        ->and(app(VacationOrderTemplateUpgrader::class)->run())->toMatchArray(['roles' => [], 'texts' => [], 'current' => ['istifade_olunmamis_mezuniyyet_kompensasiyasi']]);
});

it('offers the remaining days per work year in the order form and fills the picked work year', function (): void {
    $this->actingAs(grantAllStructures(User::factory()->create()->givePermissionTo(Permission::findOrCreate('add-orders', 'web'))));
    $personnel = vwPersonnel();
    $template = vwTemplate('emek_mezuniyyeti');
    $key = fn (string $label): string => (string) collect($template->manualFields())->firstWhere('label', $label)['key'];

    Livewire::test(OrderComposer::class, ['presetCode' => 'emek_mezuniyyeti', 'personnelId' => $personnel->id])
        ->set('fields.'.$key('Başlama tarixi'), '2026-10-12')
        ->assertSee(__('orders::order_composer.vacation.by_work_year'))
        ->assertSee('15.03.2025 – 14.03.2026')
        ->call('useVacationWorkYear', '2025-03-15')
        ->assertSet('fields.'.$key('İş ili'), '2025-03-15');
});
