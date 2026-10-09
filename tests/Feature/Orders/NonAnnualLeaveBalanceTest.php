<?php

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\Vacation;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Vacation\Application\Services\LegacyVacationMigrator;
use App\Services\Vacation\VacationBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * ƏM m.112.1: the annual (labour) leave balance is one leave kind; paternity leave
 * (social leave, m.125.4), education leave (m.123) and unpaid leave (m.128–130) are
 * others and must not consume it.
 */

beforeEach(function (): void {
    Storage::fake('local');
    $this->actingAs(User::factory()->create());

    foreach (['ataliq_mezuniyyeti', 'tehsil_mezuniyyeti', 'odenissiz_mezuniyyet', 'emek_mezuniyyeti'] as $code) {
        $this->artisan('orders:seed-word-templates', ['--only' => $code])->assertSuccessful();
    }
});

function nalbTemplate(string $code): OrderWordTemplate
{
    return OrderWordTemplate::query()->where('code', $code)->firstOrFail();
}

function nalbPersonnel(): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'TB'.Str::upper(Str::random(6)),
        'surname' => 'Bayramov',
        'name' => 'Ruslan',
        'patronymic' => 'Bəxtiyar',
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
        'join_work_date' => '2020-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

/**
 * @param  array<string,string>  $fieldsByLabel
 * @return array<string,string>
 */
function nalbFields(OrderWordTemplate $template, array $fieldsByLabel): array
{
    $fields = [];
    foreach ($fieldsByLabel as $label => $value) {
        $fields[(string) collect($template->manualFields())->firstWhere('label', $label)['key']] = $value;
    }

    return $fields;
}

/**
 * @param  array<string,string>  $fieldsByLabel
 */
function nalbIssueAndApprove(string $code, Personnel $personnel, array $fieldsByLabel, string $number): OrderLog
{
    $template = nalbTemplate($code);
    $outcome = app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
        $template->code, $personnel->id, null, null, null, nalbFields($template, $fieldsByLabel), $number, '08.10.2026', 'Bakı şəhəri',
    ), false);

    expect($outcome->isSaved())->toBeTrue(json_encode($outcome->errors, JSON_UNESCAPED_UNICODE).' '.$outcome->message);

    $order = OrderLog::query()->where('order_no', $number)->sole();
    app(OrderStatusTransitionService::class)->approve($order);

    return $order;
}

/**
 * A calendar-year balance as the previous ledger kept it, moved into the work-year ledger
 * the way the upgrade migration does.
 */
function nalbBalance(Personnel $personnel, int $total = 30, int $remaining = 30): Vacation
{
    $row = Vacation::query()->create([
        'tabel_no' => $personnel->tabel_no, 'year' => 2026, 'reserved_date_month' => null,
        'vacation_days_total' => $total, 'remaining_days' => $remaining,
    ]);

    app(LegacyVacationMigrator::class)->migrate();

    return $row;
}

function nalbRemaining(Personnel $personnel): int
{
    return app(VacationBalanceService::class)->balanceOn($personnel, CarbonImmutable::parse('2026-12-31'))['remaining'];
}

$period = fn (string $days, string $start, string $end, string $return): array => [
    'Gün sayı' => $days, 'Başlama tarixi' => $start, 'Bitmə tarixi' => $end,
    'İşə başlama tarixi' => $return, 'Əsas mətni' => 'ərizə',
];

it('seeds paternity, education and unpaid leave on effects that leave the annual balance alone', function (): void {
    expect(nalbTemplate('ataliq_mezuniyyeti')->effect)->toBe('social_leave')
        ->and(nalbTemplate('tehsil_mezuniyyeti')->effect)->toBe('education_leave')
        ->and(nalbTemplate('odenissiz_mezuniyyet')->effect)->toBe('unpaid_leave')
        ->and(nalbTemplate('emek_mezuniyyeti')->effect)->toBe('vacation')
        // ƏM m.125.4: 14 calendar days.
        ->and(collect(nalbTemplate('ataliq_mezuniyyeti')->manualFields())->firstWhere('label', 'Gün sayı')['default'])->toBe('14');
});

it('puts the employee on paternity and education leave without deducting the annual balance', function () use ($period): void {
    $personnel = nalbPersonnel();
    $balance = nalbBalance($personnel);

    nalbIssueAndApprove('ataliq_mezuniyyeti', $personnel, $period('14', '2026-10-12', '2026-10-25', '2026-10-26'), '1-A');
    nalbIssueAndApprove('tehsil_mezuniyyeti', $personnel, ['Təhsil məlumatı' => 'BDU qiyabi'] + $period('10', '2026-11-02', '2026-11-11', '2026-11-12'), '2-T');

    expect(PersonnelVacation::query()->count())->toBe(2)
        ->and(nalbRemaining($personnel))->toBe(30);

    // Revoking does not "give back" days that were never taken.
    app(OrderStatusTransitionService::class)->revert(OrderLog::query()->where('order_no', '1-A')->sole(), 'Səhv tərtib edilib');

    expect(nalbRemaining($personnel))->toBe(30)
        ->and(PersonnelVacation::query()->count())->toBe(1);
});

it('does not deduct unpaid leave either', function (): void {
    $personnel = nalbPersonnel();
    $balance = nalbBalance($personnel);

    nalbIssueAndApprove('odenissiz_mezuniyyet', $personnel, [
        'Səbəb' => 'ailə vəziyyəti ilə əlaqədar', 'Başlama tarixi' => '2026-10-12', 'Bitmə tarixi' => '2026-10-18',
        'İşə başlama tarixi' => '2026-10-19', 'Əsas mətni' => 'ərizə',
    ], '3-O');

    expect((int) PersonnelVacation::query()->sole()->duration)->toBe(7)
        ->and(nalbRemaining($personnel))->toBe(30);
});

it('still deducts annual labour leave', function () use ($period): void {
    $personnel = nalbPersonnel();
    $balance = nalbBalance($personnel);

    nalbIssueAndApprove('emek_mezuniyyeti', $personnel, ['İş ili' => '2026-01-01'] + $period('10', '2026-10-12', '2026-10-21', '2026-10-22'), '4-E');

    expect(nalbRemaining($personnel))->toBe(20);
});

it('reclassifies old installs and gives back the days approved orders took, once', function () use ($period): void {
    $personnel = nalbPersonnel();

    // An install whose catalogue still runs these types on the annual effect.
    OrderWordTemplate::query()
        ->whereIn('code', ['ataliq_mezuniyyeti', 'tehsil_mezuniyyeti', 'odenissiz_mezuniyyet'])
        ->update(['effect' => 'vacation']);
    $balance = nalbBalance($personnel);

    nalbIssueAndApprove('ataliq_mezuniyyeti', $personnel, $period('14', '2026-10-12', '2026-10-25', '2026-10-26'), '5-A');
    nalbIssueAndApprove('tehsil_mezuniyyeti', $personnel, ['Təhsil məlumatı' => 'BDU'] + $period('6', '2026-11-02', '2026-11-07', '2026-11-09'), '6-T');
    nalbIssueAndApprove('emek_mezuniyyeti', $personnel, ['İş ili' => '2026-01-01'] + $period('5', '2026-12-01', '2026-12-05', '2026-12-07'), '7-E');

    expect(nalbRemaining($personnel))->toBe(30 - 14 - 6 - 5);

    $this->artisan('orders:reclassify-non-annual-leave', ['--dry-run' => true])->assertSuccessful();
    expect(nalbRemaining($personnel))->toBe(5)
        ->and(nalbTemplate('ataliq_mezuniyyeti')->effect)->toBe('vacation');

    $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_08_200000_reclassify_non_annual_leave_order_types.php');
    $migration->up();
    $migration->up(); // idempotent
    $this->artisan('orders:reclassify-non-annual-leave')->assertSuccessful();

    // Only the annual leave's 5 days stay deducted.
    expect(nalbRemaining($personnel))->toBe(25)
        ->and(nalbTemplate('ataliq_mezuniyyeti')->effect)->toBe('social_leave')
        ->and(nalbTemplate('tehsil_mezuniyyeti')->effect)->toBe('education_leave')
        ->and(nalbTemplate('odenissiz_mezuniyyet')->effect)->toBe('unpaid_leave');

    // Revoking an old paternity order now runs the non-deducting effect: no double refund.
    app(OrderStatusTransitionService::class)->revert(OrderLog::query()->where('order_no', '5-A')->sole(), 'Səhv tərtib edilib');
    expect(nalbRemaining($personnel))->toBe(25);
});
