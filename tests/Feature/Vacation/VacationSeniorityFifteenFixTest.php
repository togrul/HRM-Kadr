<?php

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Models\VacationBalanceEntry;
use App\Models\VacationNorm;
use App\Modules\Vacation\Application\Services\FifteenYearSeniorityFix;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Modules\Vacation\Application\Services\WorkYearBalance;
use App\Modules\Vacation\Livewire\SeniorityFifteenFix;
use App\Services\StructureScope;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

/*
 * «Düz 15 il» düzəlişi (ƏM m.116.1; docs/vacation-legal-basis.md, (b-3)): 11.10.2026-dan əvvəl
 * başlamış iş illərində düz 15 il stajlı işçilərə hesablanmış 4 gün 6-ya düzəliş ilə tamamlanır.
 */

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-11 10:00:00');
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '']);

    // Yeniləmədən əvvəlki vəziyyət («15 ildən çox», 15 xaric) və 11.10.2026 yeniləməsi.
    VacationNorm::query()->where('statutory_key', 'seniority_15')->whereNull('valid_to')->sole()->forceFill(['min_inclusive' => false])->save();
    $this->structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $this->other = Structure::query()->create(['name' => 'Binə', 'shortname' => 'B']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function sffPersonnel(Structure $structure, string $join, ?string $leave = null): Personnel
{
    $position = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'SF'.Str::upper(Str::random(6)),
        'surname' => 'Əliyev',
        'name' => 'Vüqar',
        'patronymic' => 'Tofiq',
        'birthdate' => '1980-01-01',
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
        'leave_work_date' => $leave,
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

function sffMigrate(): void
{
    (require base_path('app/Modules/Vacation/Database/Migrations/2026_10_11_190000_include_full_fifteen_years_in_seniority_leave.php'))->up();
}

function sffYear(Personnel $personnel, int $sequence): WorkYearBalance
{
    return collect(app(VacationBalanceService::class)->workYears($personnel->fresh(), CarbonImmutable::parse('2026-10-11')))
        ->first(fn (WorkYearBalance $b): bool => ! $b->isConditions() && $b->period->sequence === $sequence);
}

function sffAdmin(?array $structures = null): User
{
    $user = User::factory()->create()->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));

    return $structures === null ? grantAllStructures($user) : grantStructures($user, $structures);
}

it('finds exactly-15-year work years started before the update, current and finished, and posts +2 once', function (): void {
    // 16-cı iş ili 01.06.2026–31.05.2027, başlanğıcda düz 15 il → 4 gün hesablanıb.
    $current = sffPersonnel($this->structure, '2011-06-01');
    // 16-cı iş ili 01.06.2025–31.05.2026 bitib, düz 15 il → 4; 17-ci il 16 illə → 6.
    $finished = sffPersonnel($this->structure, '2010-06-01');
    // 16-cı iş ili 01.12.2026-da başlayacaq — yeni qayda ilə 6, təsirlənmir.
    $future = sffPersonnel($this->structure, '2011-12-01');
    // 14 il — təsirlənmir.
    sffPersonnel($this->structure, '2012-06-01');

    sffMigrate();

    expect(sffYear($current, 16)->breakdown->seniority)->toBe(4)
        ->and(sffYear($finished, 16)->breakdown->seniority)->toBe(4)
        ->and(sffYear($finished, 17)->breakdown->seniority)->toBe(6);

    $fix = app(FifteenYearSeniorityFix::class);
    $preview = $fix->preview(StructureScope::all());

    expect(collect($preview['active'])->map(fn (array $r): array => [$r['tabel_no'], $r['sequence'], $r['start'], $r['seniority_days'], $r['missing'], $r['applied'], $r['finished']])->sortBy(0)->values()->all())
        ->toEqualCanonicalizing([
            [$current->tabel_no, 16, '2026-06-01', 4, 2, false, false],
            [$finished->tabel_no, 16, '2025-06-01', 4, 2, false, true],
        ])
        ->and($preview['terminated'])->toBe([])
        ->and(collect($preview['active'])->pluck('tabel_no'))->not->toContain($future->tabel_no);

    $before = sffYear($current, 16)->total();

    expect($fix->apply(StructureScope::all(), null))->toBe(2);

    // 21 + 4 = 25 → düzəlişlə 27 (21 + 6).
    expect(sffYear($current, 16)->adjusted)->toBe(2)
        ->and(sffYear($current, 16)->total())->toBe($before + 2)
        ->and(sffYear($current, 16)->total())->toBe(27)
        ->and(sffYear($finished, 16)->adjusted)->toBe(2)
        ->and(sffYear($finished, 17)->adjusted)->toBe(0);

    $entry = VacationBalanceEntry::query()->where('tabel_no', $current->tabel_no)->sole();
    expect($entry->only(['kind', 'days', 'source']))->toBe([
        'kind' => VacationBalanceEntry::KIND_ADJUSTMENT,
        'days' => 2,
        'source' => FifteenYearSeniorityFix::sourceFor($current->tabel_no, 16),
    ])->and($entry->note)->toContain('m.116.1');

    // İdempotent: ikinci işə salınma heç nə yazmır, önizləmə «yazılıb» göstərir.
    expect($fix->apply(StructureScope::all(), null))->toBe(0)
        ->and(VacationBalanceEntry::query()->where('kind', VacationBalanceEntry::KIND_ADJUSTMENT)->count())->toBe(2)
        ->and(collect($fix->preview(StructureScope::all())['active'])->every(fn (array $r): bool => $r['applied'] && $r['applied_days'] === 2))->toBeTrue();

    // Audit jurnalı.
    expect(Activity::query()->where('log_name', 'vacation')->where('event', 'seniority_fifteen_fix')->count())->toBe(2);
});

it('lists terminated employees separately and does not post to their balance', function (): void {
    // 16-cı iş ili 01.03.2026-da düz 15 illə başlayıb, 30.09.2026-da xitam.
    $left = sffPersonnel($this->structure, '2011-03-01', '2026-09-30');
    // 16-cı iş ili 01.01.2026, xitam 31.01.2026 — KMQ16-dan (18.02.2026) əvvəl.
    $early = sffPersonnel($this->structure, '2011-01-01', '2026-01-31');

    sffMigrate();

    $fix = app(FifteenYearSeniorityFix::class);
    $preview = $fix->preview(StructureScope::all());

    expect($preview['active'])->toBe([])
        ->and(collect($preview['terminated'])->mapWithKeys(fn (array $r): array => [$r['tabel_no'] => [$r['missing'], $r['leave_date'], $r['before_kmq16']]])->all())
        ->toEqual([
            $left->tabel_no => [2, '2026-09-30', false],
            $early->tabel_no => [2, '2026-01-31', true],
        ]);

    expect($fix->apply(StructureScope::all(), null))->toBe(0)
        ->and(VacationBalanceEntry::query()->count())->toBe(0);
});

it('does nothing for work years that already received 6 days under the new rule', function (): void {
    // Təzə qurulum: qayda sətirdə yerində dəyişir, bütün iş illəri 6 gün alır.
    VacationNorm::query()->where('statutory_key', 'seniority_15')->whereNull('valid_to')->sole()->forceFill(['min_inclusive' => true])->save();
    $personnel = sffPersonnel($this->structure, '2011-06-01');

    expect(sffYear($personnel, 16)->breakdown->seniority)->toBe(6)
        ->and(app(FifteenYearSeniorityFix::class)->preview(StructureScope::all()))->toBe(['active' => [], 'terminated' => []]);
});

it('requires access-admin and shows only employees in the user structure scope', function (): void {
    $mine = sffPersonnel($this->structure, '2011-06-01');
    $foreign = sffPersonnel($this->other, '2011-06-01');
    sffMigrate();

    $this->actingAs(grantAllStructures(User::factory()->create()));
    Livewire::test(SeniorityFifteenFix::class)->assertForbidden();

    $this->actingAs(sffAdmin([$this->structure->id]));

    $component = Livewire::test(SeniorityFifteenFix::class)
        ->call('scan')
        ->assertSee($mine->tabel_no)
        ->assertDontSee($foreign->tabel_no)
        ->call('confirmApply')
        ->assertDispatched('confirm-action')
        ->call('apply')
        ->assertDispatched('notify', type: 'success');

    expect(VacationBalanceEntry::query()->where('tabel_no', $mine->tabel_no)->sum('days'))->toBe(2)
        ->and(VacationBalanceEntry::query()->where('tabel_no', $foreign->tabel_no)->exists())->toBeFalse();

    // Təkrar təsdiq: yazılacaq yoxdur.
    $component->call('confirmApply')->assertNotDispatched('confirm-action');
    expect(VacationBalanceEntry::query()->count())->toBe(1);
});

it('is embedded in the seniority group of the norms screen and runs from the console', function (): void {
    $personnel = sffPersonnel($this->structure, '2011-06-01');
    sffMigrate();

    $this->artisan('vacation:seniority-fifteen-fix')->assertSuccessful();
    expect(VacationBalanceEntry::query()->count())->toBe(0);

    $this->artisan('vacation:seniority-fifteen-fix', ['--apply' => true])->assertSuccessful();
    $this->artisan('vacation:seniority-fifteen-fix', ['--apply' => true])->assertSuccessful();
    expect(VacationBalanceEntry::query()->where('tabel_no', $personnel->tabel_no)->sum('days'))->toBe(2);

    $this->actingAs(sffAdmin());
    $this->get(route('admin.vacation-norms', ['group' => 'seniority']))
        ->assertOk()
        ->assertSee(__('vacation::seniority_fix.title'));
});
