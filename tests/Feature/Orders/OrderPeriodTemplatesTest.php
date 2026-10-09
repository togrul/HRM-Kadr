<?php

namespace Tests\Feature\Orders;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\BusinessTrips\Livewire\BusinessTrips;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\OrderComposer;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The period order types (business trip, maternity, unpaid leave…) from the standard
 * catalogue: their fields, the date rules on issue and again on approval, and the
 * register records approval creates and revocation removes.
 */
class OrderPeriodTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        foreach (['ezamiyyet', 'analiq_mezuniyyeti', 'odenissiz_mezuniyyet', 'emek_mezuniyyeti', 'intizam_tenbehi', 'evezetme', 'emek_haqqi_deyisme'] as $code) {
            $this->artisan('orders:seed-word-templates', ['--only' => $code])->assertSuccessful();
        }
    }

    public function test_the_catalogue_registers_the_new_order_types(): void
    {
        $trip = $this->template('ezamiyyet');
        $this->assertSame('business_trip', $trip->effect);
        $perDiem = collect($trip->manualFields())->firstWhere('label', 'Ezamiyyə xərcləri (gündəlik)');
        $this->assertFalse($perDiem['required']);

        $maternity = $this->template('analiq_mezuniyyeti');
        $this->assertSame('social_leave', $maternity->effect);
        $this->assertSame('126', collect($maternity->manualFields())->firstWhere('label', 'Gün sayı')['default']);

        $this->assertSame('disciplinary', $this->template('intizam_tenbehi')->effect);
        $this->assertSame('substitution', $this->template('evezetme')->effect);
        $this->assertSame('salary_change', $this->template('emek_haqqi_deyisme')->effect);

        // The document is company-neutral: no customer name or person baked in.
        foreach (['ezamiyyet', 'analiq_mezuniyyeti', 'intizam_tenbehi', 'evezetme', 'emek_haqqi_deyisme'] as $code) {
            Storage::disk('local')->assertExists($this->template($code)->docx_path);
        }
    }

    public function test_missing_only_seeds_codes_the_install_does_not_have(): void
    {
        $this->template('ezamiyyet')->update(['label' => 'Ezamiyyət (redaktə)']);

        $this->artisan('orders:seed-word-templates', ['--missing' => true])->assertSuccessful();

        $this->assertSame('Ezamiyyət (redaktə)', $this->template('ezamiyyet')->label);
        $this->assertNotNull(OrderWordTemplate::query()->where('code', 'xitam')->first());
    }

    public function test_the_migration_adds_the_new_types_to_an_install_that_runs_the_catalogue(): void
    {
        OrderWordTemplate::query()->whereIn('code', ['ezamiyyet', 'evezetme'])->delete();
        $this->template('analiq_mezuniyyeti')->update(['label' => 'Analıq (redaktə)']);

        $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_08_160000_register_additional_order_word_templates.php');
        $migration->up();
        $migration->up(); // idempotent

        $this->assertSame(1, OrderWordTemplate::query()->where('code', 'ezamiyyet')->count());
        $this->assertSame(1, OrderWordTemplate::query()->where('code', 'evezetme')->count());
        $this->assertSame('Analıq (redaktə)', $this->template('analiq_mezuniyyeti')->label);
    }

    public function test_an_unpaid_leave_with_inverted_dates_is_rejected_on_issue(): void
    {
        $personnel = $this->makePersonnel();
        $template = $this->template('odenissiz_mezuniyyet');

        $outcome = $this->issue($template, $personnel, [
            'Səbəb' => 'ailə vəziyyəti ilə əlaqədar',
            'Başlama tarixi' => '2026-10-20',
            'Bitmə tarixi' => '2026-10-10',
            'İşə başlama tarixi' => '2026-10-01',
            'Əsas mətni' => 'ərizə',
        ]);

        $this->assertFalse($outcome->isSaved());
        $this->assertSame(
            [$this->errorKey($template, 'Bitmə tarixi'), $this->errorKey($template, 'İşə başlama tarixi')],
            array_keys($outcome->errors),
        );
        $this->assertSame(__('orders::order_composer.errors.leave_dates.end_before_start'), $outcome->errors[$this->errorKey($template, 'Bitmə tarixi')]);
        $this->assertSame(0, OrderLog::query()->count());
    }

    public function test_the_day_count_cannot_exceed_the_period_and_the_work_year_must_fit(): void
    {
        $personnel = $this->makePersonnel();
        $template = $this->template('emek_mezuniyyeti');

        $outcome = $this->issue($template, $personnel, [
            'İş ili' => '2027-01-01',
            'Gün sayı' => '20',
            'Başlama tarixi' => '2026-10-01',
            'Bitmə tarixi' => '2026-10-10',
            'İşə başlama tarixi' => '2026-10-11',
            'Əsas mətni' => 'ərizə',
        ]);

        $this->assertFalse($outcome->isSaved());
        $this->assertArrayHasKey($this->errorKey($template, 'Gün sayı'), $outcome->errors);
        $this->assertArrayHasKey($this->errorKey($template, 'İş ili'), $outcome->errors);
    }

    public function test_an_approved_business_trip_order_creates_the_trip_and_revoking_removes_it(): void
    {
        $personnel = $this->makePersonnel();

        $outcome = $this->issue($this->template('ezamiyyet'), $personnel, $this->tripFields());
        $this->assertTrue($outcome->isSaved(), json_encode($outcome->errors, JSON_UNESCAPED_UNICODE).' '.$outcome->message);

        $order = OrderLog::query()->sole();
        app(OrderStatusTransitionService::class)->approve($order);

        $trip = PersonnelBusinessTrip::query()->sole();
        $this->assertSame($personnel->tabel_no, $trip->tabel_no);
        $this->assertSame('Gəncə şəhəri', $trip->location);
        $this->assertSame('təlimdə iştirak', $trip->description);
        $this->assertSame('2026-11-02', $trip->getRawOriginal('start_date'));
        $this->assertSame('qatar', $trip->getAttribute('attributes')['transport']);
        $this->assertSame($order->order_no, $trip->order_no);

        // The register filters by the order type it came from.
        $this->assertSame(1, PersonnelBusinessTrip::query()->filter(['order_type_id' => 'tpl:ezamiyyet'])->count());
        $this->assertSame(0, PersonnelBusinessTrip::query()->filter(['order_type_id' => 'tpl:evezetme'])->count());
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-business_trips', 'web'));
        $this->actingAs($viewer);
        $options = Livewire::test(BusinessTrips::class)->instance()->orderTypeOptions();
        $this->assertContains(['id' => 'tpl:ezamiyyet', 'label' => 'Ezamiyyət'], $options);

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Səhv tərtib edilib');

        $this->assertSame(0, PersonnelBusinessTrip::withTrashed()->count());
    }

    public function test_a_business_trip_overlapping_a_vacation_is_refused_on_issue_and_on_approval(): void
    {
        $personnel = $this->makePersonnel();

        // A draft issued while the employee was free…
        $this->assertTrue($this->issue($this->template('ezamiyyet'), $personnel, $this->tripFields())->isSaved());

        // …then a vacation lands on the same days.
        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no, 'vacation_places' => '', 'duration' => 3,
            'start_date' => '2026-11-04', 'end_date' => '2026-11-06', 'return_work_date' => '2026-11-07',
            'order_given_by' => 'HR', 'vacation_days_total' => 0, 'remaining_days' => 0,
        ]);

        $second = $this->issue($this->template('ezamiyyet'), $personnel, $this->tripFields(), '101-M');
        $this->assertFalse($second->isSaved());
        $this->assertStringContainsString('məzuniyyət', (string) $second->message);

        try {
            app(OrderStatusTransitionService::class)->approve(OrderLog::query()->sole());
            $this->fail('An overlapping draft must not be approved.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('məzuniyyət qeydi var', $exception->getMessage());
        }

        $this->assertSame(0, PersonnelBusinessTrip::query()->count());
    }

    public function test_an_invalid_draft_stored_before_the_rules_cannot_be_approved(): void
    {
        $personnel = $this->makePersonnel();
        $template = $this->template('odenissiz_mezuniyyet');

        $valid = [
            'Səbəb' => 'ailə', 'Başlama tarixi' => '2026-10-01', 'Bitmə tarixi' => '2026-10-05',
            'İşə başlama tarixi' => '2026-10-06', 'Əsas mətni' => 'ərizə',
        ];
        $this->assertTrue($this->issue($template, $personnel, $valid)->isSaved());

        // Simulate the audit's draft: 20.10 – 10.10, back at work 01.10.
        $order = OrderLog::query()->sole();
        $snapshot = $order->template_snapshot;
        $snapshot['fields'] = $this->byLabel($template, [...$valid, 'Başlama tarixi' => '2026-10-20', 'Bitmə tarixi' => '2026-10-10', 'İşə başlama tarixi' => '2026-10-01']);
        $order->forceFill(['template_snapshot' => $snapshot])->save();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('orders::order_composer.errors.leave_dates.end_before_start'));

        try {
            app(OrderStatusTransitionService::class)->approve($order->fresh());
        } finally {
            $this->assertSame(0, PersonnelVacation::query()->count());
        }
    }

    public function test_maternity_leave_creates_a_vacation_record_without_touching_the_annual_balance(): void
    {
        $personnel = $this->makePersonnel();

        $this->assertTrue($this->issue($this->template('analiq_mezuniyyeti'), $personnel, [
            'Gün sayı' => '126', 'Başlama tarixi' => '2026-11-01', 'Bitmə tarixi' => '2027-03-06',
            'İşə başlama tarixi' => '2027-03-07', 'Əsas mətni' => 'xəstəlik vərəqəsi',
        ])->isSaved());

        app(OrderStatusTransitionService::class)->approve(OrderLog::query()->sole());

        $vacation = PersonnelVacation::query()->sole();
        $this->assertSame(126, (int) $vacation->duration);
        $this->assertSame(0, \App\Models\Vacation::query()->count());
    }

    public function test_an_inactive_employee_cannot_be_sent_on_a_trip(): void
    {
        $personnel = $this->makePersonnel(['leave_work_date' => '2026-01-01']);

        $outcome = $this->issue($this->template('ezamiyyet'), $personnel, $this->tripFields());

        // The business trip is a multi-participant order: the refusal names the participant.
        $this->assertFalse($outcome->isSaved());
        $this->assertSame(__('orders::order_composer.errors.employee_inactive'), $outcome->errors['participants.0']);
        $this->assertStringContainsString('Bayramov', (string) $outcome->message);
    }

    public function test_the_composer_prefills_the_day_count_and_derives_the_dates(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));
        $this->actingAs($user);

        $template = $this->template('analiq_mezuniyyeti');
        $token = fn (string $label): string => $this->token($template, $label);

        Livewire::test(OrderComposer::class, ['presetCode' => 'analiq_mezuniyyeti'])
            ->assertSet('fields.'.$token('Gün sayı'), '126')
            ->set('fields.'.$token('Başlama tarixi'), '2026-11-01')
            ->assertSet('fields.'.$token('Bitmə tarixi'), '2027-03-06')
            ->assertSet('fields.'.$token('İşə başlama tarixi'), '2027-03-07');

        $unpaid = $this->template('odenissiz_mezuniyyet');
        Livewire::test(OrderComposer::class, ['presetCode' => 'odenissiz_mezuniyyet'])
            ->set('fields.'.$this->token($unpaid, 'Başlama tarixi'), '2026-10-10')
            ->set('fields.'.$this->token($unpaid, 'Bitmə tarixi'), '2026-10-12')
            ->assertSet('fields.'.$this->token($unpaid, 'İşə başlama tarixi'), '2026-10-13');
    }

    /**
     * @return array<string,string>
     */
    private function tripFields(): array
    {
        return [
            'Ezamiyyətin məqsədi' => 'təlimdə iştirak',
            'Başlama tarixi' => '2026-11-02',
            'Bitmə tarixi' => '2026-11-05',
            'Ezamiyyə yeri' => 'Gəncə şəhəri',
            'Nəqliyyat' => 'qatar',
            'İşə başlama tarixi' => '2026-11-06',
            'Əsas mətni' => 'xidməti qeyd',
        ];
    }

    /**
     * @param  array<string,string>  $fieldsByLabel
     */
    private function issue(OrderWordTemplate $template, Personnel $personnel, array $fieldsByLabel, string $number = '100-M')
    {
        return app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
            $template->code, $personnel->id, null, null, null, $this->byLabel($template, $fieldsByLabel), $number, '08.10.2026', 'Bakı şəhəri',
        ), false);
    }

    /**
     * @param  array<string,string>  $fieldsByLabel
     * @return array<string,string>
     */
    private function byLabel(OrderWordTemplate $template, array $fieldsByLabel): array
    {
        $fields = [];
        foreach ($fieldsByLabel as $label => $value) {
            $fields[$this->token($template, $label)] = $value;
        }

        return $fields;
    }

    private function token(OrderWordTemplate $template, string $label): string
    {
        return (string) collect($template->manualFields())->firstWhere('label', $label)['key'];
    }

    private function errorKey(OrderWordTemplate $template, string $label): string
    {
        return 'fields.'.$this->token($template, $label);
    }

    private function template(string $code): OrderWordTemplate
    {
        return OrderWordTemplate::query()->where('code', $code)->firstOrFail();
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function makePersonnel(array $overrides = []): Personnel
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
            ...$overrides,
        ]));
    }
}
