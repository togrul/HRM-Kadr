<?php

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderNumbering;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\OrderComposer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;

/**
 * Automatic order numbers: assigned at approval from a per-install format, counted per
 * scope (all types / per type) and year, never reused; manual numbering stays as it was.
 */
beforeEach(function (): void {
    Storage::fake('local');
});

function configureNumbering(string $format, string $scope = 'global', bool $yearly = true, string $typeCodes = ''): void
{
    foreach ([
        OrderNumbering::SETTING_FORMAT => [$format, 'string'],
        OrderNumbering::SETTING_SCOPE => [$scope, 'string'],
        OrderNumbering::SETTING_YEARLY_RESET => [$yearly ? '1' : '0', 'bool'],
        OrderNumbering::SETTING_TYPE_CODES => [$typeCodes, 'string'],
    ] as $name => [$value, $type]) {
        Setting::query()->updateOrCreate(['name' => $name], ['value' => $value, 'type' => $type]);
    }
}

/** A pending order still holding a provisional number. */
function provisionalOrder(string $templateCode = 'leave', string $orderDate = '14.05.2026-cı il'): OrderLog
{
    return OrderLog::query()->create([
        'order_no' => app(OrderNumbering::class)->provisional(),
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => OrderIssueService::STATUS_PENDING,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => ['template_code' => $templateCode, 'order_date_text' => $orderDate, 'fields' => []],
    ]);
}

function numberingApprove(OrderLog $order): string
{
    app(OrderStatusTransitionService::class)->approve($order);

    return (string) $order->fresh()->order_no;
}

it('renders the format tokens', function (): void {
    configureNumbering('{il}/{növ}-{N:3}', typeCodes: 'ezamiyyet=EZ, mezuniyyet = M');
    $numbering = app(OrderNumbering::class);

    expect($numbering->render('{il}/{növ}-{N:3}', 7, 2026, $numbering->typeCode('ezamiyyet')))->toBe('2026/EZ-007')
        ->and($numbering->render('{N}-K', 12, 2026, 'X'))->toBe('12-K')
        ->and($numbering->typeCode('mezuniyyet'))->toBe('M')
        ->and($numbering->typeCode('leave'))->toBe('LEAVE')
        // A format without a counter still yields distinct numbers.
        ->and($numbering->render('ƏM', 3, 2026, 'X'))->toBe('ƏM-3');
});

it('keeps manual numbering when no format is configured', function (): void {
    $numbering = app(OrderNumbering::class);
    expect($numbering->isAutomatic())->toBeFalse();

    $order = OrderLog::query()->create([
        'order_no' => '55-K',
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => OrderIssueService::STATUS_PENDING,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => [],
    ]);

    expect(numberingApprove($order))->toBe('55-K')
        ->and(DB::table('order_number_sequences')->count())->toBe(0);
});

it('assigns consecutive numbers at approval and skips numbers already typed by hand', function (): void {
    configureNumbering('{il}/ƏM-{N:3}');
    OrderLog::query()->create([
        'order_no' => '2026/ƏM-002',
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => OrderIssueService::STATUS_PENDING,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => [],
    ]);

    $numbers = collect(range(1, 4))->map(fn () => numberingApprove(provisionalOrder()))->all();

    expect($numbers)->toBe(['2026/ƏM-001', '2026/ƏM-003', '2026/ƏM-004', '2026/ƏM-005'])
        ->and(DB::table('order_number_sequences')->where('scope_key', 'global')->where('year', 2026)->value('last_value'))->toBe(5);
});

it('never hands out the same number twice, even for interleaved approvals', function (): void {
    configureNumbering('{N}');
    $orders = collect(range(1, 20))->map(fn () => provisionalOrder());

    // Approve in a scrambled order, the way concurrent users would.
    $numbers = $orders->shuffle()->map(fn (OrderLog $order) => numberingApprove($order));

    expect($numbers->unique()->count())->toBe(20)
        ->and($numbers->sort(SORT_NUMERIC)->values()->all())->toBe(array_map('strval', range(1, 20)));
});

it('rolls the counter back with a failed approval', function (): void {
    configureNumbering('{N}');
    numberingApprove(provisionalOrder());

    try {
        DB::transaction(function (): void {
            app(OrderNumbering::class)->assign('leave', now());

            throw new RuntimeException('approval failed');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(numberingApprove(provisionalOrder()))->toBe('2');
});

it('restarts every year when yearly reset is on, and keeps counting when it is off', function (): void {
    configureNumbering('{il}-{N}');
    expect(numberingApprove(provisionalOrder(orderDate: '20.12.2025')))->toBe('2025-1')
        ->and(numberingApprove(provisionalOrder(orderDate: '05.01.2026')))->toBe('2026-1')
        ->and(numberingApprove(provisionalOrder(orderDate: '06.01.2026')))->toBe('2026-2');

    configureNumbering('C{N}', yearly: false);
    expect(numberingApprove(provisionalOrder(orderDate: '20.12.2025')))->toBe('C1')
        ->and(numberingApprove(provisionalOrder(orderDate: '05.01.2026')))->toBe('C2')
        ->and(DB::table('order_number_sequences')->where('year', 0)->value('last_value'))->toBe(2);
});

it('counts per order type when the scope is per type', function (): void {
    configureNumbering('{növ}-{N}', scope: 'type', typeCodes: 'leave=M, trip=EZ');

    expect(numberingApprove(provisionalOrder('leave')))->toBe('M-1')
        ->and(numberingApprove(provisionalOrder('trip')))->toBe('EZ-1')
        ->and(numberingApprove(provisionalOrder('leave')))->toBe('M-2');

    configureNumbering('G{N}', scope: 'global');
    expect(numberingApprove(provisionalOrder('leave')))->toBe('G1')
        ->and(numberingApprove(provisionalOrder('trip')))->toBe('G2');
});

it('reads the counter scope from the on/off switch and from the earlier free-text values', function (): void {
    foreach (['1' => OrderNumbering::SCOPE_TYPE, '0' => OrderNumbering::SCOPE_GLOBAL, 'type' => OrderNumbering::SCOPE_TYPE, 'global' => OrderNumbering::SCOPE_GLOBAL] as $stored => $expected) {
        Setting::query()->updateOrCreate(['name' => OrderNumbering::SETTING_SCOPE], ['value' => $stored, 'type' => 'bool']);

        expect((new OrderNumbering)->scope())->toBe($expected);
    }
});

it('turns the earlier free-text counter scope into the switch', function (): void {
    $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_09_150000_turn_order_number_scope_into_switch.php');

    foreach (['type' => '1', 'növ' => '1', 'global' => '0', '' => '0'] as $stored => $expected) {
        Setting::query()->updateOrCreate(['name' => OrderNumbering::SETTING_SCOPE], ['value' => $stored, 'type' => 'string']);

        $migration->up();

        $row = Setting::query()->where('name', OrderNumbering::SETTING_SCOPE)->first();
        expect((string) $row->getRawOriginal('value'))->toBe($expected)
            ->and($row->type)->toBe('bool');
    }
});

it('keeps a hand-typed number and the assigned one through a revert', function (): void {
    configureNumbering('{N}');

    $manual = provisionalOrder();
    $manual->update(['order_no' => '77-X']);
    expect(numberingApprove($manual))->toBe('77-X');

    $order = provisionalOrder();
    expect(numberingApprove($order))->toBe('1');

    app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Səhv tərtib edilib');
    expect($order->fresh()->order_no)->toBe('1');

    // Re-approving keeps the number; the counter does not move.
    expect(numberingApprove($order->fresh()))->toBe('1')
        ->and(numberingApprove(provisionalOrder()))->toBe('2');
});

it('refuses to approve a provisional order once numbering has been switched off', function (): void {
    configureNumbering('{N}');
    $order = provisionalOrder();
    configureNumbering('');

    expect(fn () => numberingApprove($order))->toThrow(DomainException::class, __('orders::order_composer.errors.number_missing'));
    expect((int) $order->fresh()->status_id)->toBe(OrderIssueService::STATUS_PENDING);
});

it('issues without a number, prints none, and prints the assigned number at approval', function (): void {
    configureNumbering('{il}/ƏM-{N:3}');
    seedNumberedTemplate();
    $personnel = numberingPersonnel();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));
    $this->actingAs(grantAllStructures($user));

    $composer = Livewire::test(OrderComposer::class, ['presetCode' => 'numbered', 'personnelId' => $personnel->id])
        ->assertSee(__('orders::order_composer.hints.auto_number', ['format' => '{il}/ƏM-{N:3}']))
        ->set('orderNumber', '')
        ->set('orderDate', '2026-05-14')
        ->set('fields', ['var_2' => 'birinci'])
        ->call('issue')
        ->assertHasNoErrors();

    $order = OrderLog::query()->latest('id')->firstOrFail();
    expect(OrderNumbering::isProvisional($order->order_no))->toBeTrue()
        ->and($order->personnels()->count())->toBe(1);

    $docx = Storage::disk('local')->path((string) data_get($order->template_snapshot, 'docx_path'));
    expect(numberingDocText($docx))->toContain('Əmr № .')->not->toContain('~');

    // Editing shows an empty number field and keeps the placeholder on save.
    Livewire::test(OrderComposer::class, ['orderId' => $order->id])
        ->assertSet('orderNumber', '')
        ->call('issue')
        ->assertHasNoErrors();
    expect($order->fresh()->order_no)->toBe($order->order_no);

    app(OrderStatusTransitionService::class)->approve($order->fresh());

    $approved = $order->fresh();
    expect($approved->order_no)->toBe('2026/ƏM-001')
        ->and($approved->personnels()->count())->toBe(1)
        ->and(numberingDocText(Storage::disk('local')->path((string) data_get($approved->template_snapshot, 'docx_path'))))
        ->toContain('Əmr № 2026/ƏM-001.');
});

it('still requires a typed number when numbering is manual, and rejects a duplicate', function (): void {
    seedNumberedTemplate();
    $personnel = numberingPersonnel();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));
    $this->actingAs(grantAllStructures($user));

    Livewire::test(OrderComposer::class, ['presetCode' => 'numbered', 'personnelId' => $personnel->id])
        ->set('orderNumber', '')
        ->set('fields', ['var_2' => 'birinci'])
        ->call('issue')
        ->assertHasErrors(['orderNumber'])
        ->set('orderNumber', '9-K')
        ->call('issue')
        ->assertHasNoErrors();

    Livewire::test(OrderComposer::class, ['presetCode' => 'numbered', 'personnelId' => $personnel->id])
        ->set('orderNumber', '9-K')
        ->set('fields', ['var_2' => 'ikinci'])
        ->call('issue')
        ->assertHasErrors(['orderNumber']);

    expect(OrderLog::query()->where('order_no', '9-K')->count())->toBe(1);
});

function seedNumberedTemplate(): void
{
    $phpWord = new PhpWord;
    $phpWord->addSection()->addText('Əmr № ${var_1}. ${var_3} ${var_2}');
    $tmp = tempnam(sys_get_temp_dir(), 'num_').'.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
    Storage::disk('local')->put('order-templates/numbered.docx', (string) file_get_contents($tmp));
    @unlink($tmp);

    OrderWordTemplate::query()->create([
        'code' => 'numbered',
        'label' => 'Nömrəli',
        'docx_path' => 'order-templates/numbered.docx',
        'variables' => [
            ['token' => 'var_1', 'label' => 'Nömrə', 'source' => 'auto', 'auto_key' => 'system.order_number', 'field' => null],
            ['token' => 'var_3', 'label' => 'Ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null],
            ['token' => 'var_2', 'label' => 'Qeyd', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'text']],
        ],
        'is_active' => true,
    ]);
}

function numberingDocText(string $docxPath): string
{
    $zip = new ZipArchive;
    $zip->open($docxPath);
    $xml = (string) $zip->getFromName('word/document.xml');
    $zip->close();

    return html_entity_decode(strip_tags(str_replace('<', ' <', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function numberingPersonnel(): Personnel
{
    $structure = Structure::query()->create(['name' => 'Mərkəz', 'shortname' => 'MRK']);
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
