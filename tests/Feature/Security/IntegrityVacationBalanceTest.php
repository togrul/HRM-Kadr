<?php

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderIssueOutcome;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * M7: iki gözləyən məzuniyyət əmri ayrı-ayrılıqda balansa sığır, birlikdə yox. Verilmə zamanı
 * gözləyən əmrlərin günləri sayılır, təsdiqdə isə balans kilid altında yenidən yoxlanılır.
 */

beforeEach(function (): void {
    Storage::fake('local');
    Carbon::setTestNow('2026-10-09 10:00:00');
    $this->actingAs(User::factory()->create());
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '']);
    $this->artisan('orders:seed-word-templates', ['--only' => 'emek_mezuniyyeti'])->assertSuccessful();
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function integrityLeavePersonnel(): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'IV'.Str::upper(Str::random(6)),
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
        'join_work_date' => '2024-03-15',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

function integrityIssueLeave(Personnel $personnel, int $days, string $start, string $number): OrderIssueOutcome
{
    $template = OrderWordTemplate::query()->where('code', 'emek_mezuniyyeti')->firstOrFail();
    $startDate = CarbonImmutable::parse($start);
    $values = [
        'İş ili' => '2025-03-15',
        'Gün sayı' => (string) $days,
        'Başlama tarixi' => $startDate->toDateString(),
        'Bitmə tarixi' => $startDate->addDays($days - 1)->toDateString(),
        'İşə başlama tarixi' => $startDate->addDays($days)->toDateString(),
        'Əsas mətni' => 'ərizə',
    ];
    $fields = [];
    foreach ($values as $label => $value) {
        $fields[(string) collect($template->manualFields())->firstWhere('label', $label)['key']] = $value;
    }

    return app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
        'emek_mezuniyyeti', $personnel->id, null, null, null, $fields, $number, '09.10.2026', 'Bakı şəhəri',
    ), false);
}

it('counts pending orders at issue and re-checks the balance at approval', function (): void {
    $personnel = integrityLeavePersonnel();
    $remaining = (int) app(VacationBalanceService::class)->balanceOn($personnel, CarbonImmutable::parse('2026-11-02'))['remaining'];
    expect($remaining)->toBeGreaterThan(10);

    $first = integrityIssueLeave($personnel, 5, '2026-11-02', 'IV-1');
    expect($first->isSaved())->toBeTrue($first->message ?? '');

    // Alone it would fit; with the 5 pending days it does not.
    $second = integrityIssueLeave($personnel, $remaining - 2, '2026-12-01', 'IV-2');
    expect($second->isSaved())->toBeFalse()
        ->and($second->message)->toContain((string) ($remaining - 2));

    // Something else takes the balance meanwhile (as a concurrent approval would).
    app(VacationBalanceService::class)->consume($personnel->fresh(), 2026, $remaining - 3, 'test:concurrent', CarbonImmutable::parse('2026-11-02'));

    $order = OrderLog::query()->where('order_no', 'IV-1')->sole();
    expect(fn () => app(OrderStatusTransitionService::class)->approve($order))->toThrow(DomainException::class);
    expect((int) $order->fresh()->status_id)->toBe(10)
        ->and($personnel->vacations()->count())->toBe(0);
});
