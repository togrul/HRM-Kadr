<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceOvertimeRequest;
use App\Models\Award;
use App\Models\AwardType;
use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeSubstitution;
use App\Models\Leave;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\PersonnelAward;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelPunishment;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Models\Vacation;
use App\Models\VacationBalanceEntry;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Infrastructure\Document\DisciplinarySanctionTerm;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderLookupFieldRegistry;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Infrastructure\Document\StandardOrderEffectUpgrader;
use App\Modules\Vacation\Application\Services\LegacyVacationMigrator;
use App\Services\Vacation\VacationBalanceService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Standart kataloqa əlavə olunan əmr növləri və onların təsdiqdə icra etdiyi HR
 * əməliyyatları: hər effekt təsdiqdə yazır, təsdiq geri alınanda (və ya ləğvdə) yazdığını
 * geri qaytarır.
 */
class OrderAdditionalEffectsTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_CODES = [
        'usaga_qulluq_mezuniyyeti' => 'social_leave',
        'fexri_ferman' => 'award',
        'hevale' => 'none',
        'mezuniyyetin_kecirilmesi' => 'none',
        'qisaldilmis_is_vaxti' => 'none',
        'donor_gunu' => 'paid_absence',
        'secki_komissiyasi' => 'paid_absence',
        'mulki_mudafie' => 'paid_absence',
        'mezuniyyetden_geri_cagirma' => 'vacation_recall',
        'istifade_olunmamis_mezuniyyet_kompensasiyasi' => 'vacation_compensation',
        'qeyri_is_gunu_ise_celb' => 'non_working_day_work',
        'emrin_legvi' => 'order_cancellation',
    ];

    private int $number = 100;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $this->artisan('orders:seed-word-templates')->assertSuccessful();
    }

    public function test_the_catalogue_registers_the_new_types_with_their_effects(): void
    {
        foreach (self::NEW_CODES as $code => $effect) {
            $template = $this->template($code);
            $this->assertSame($effect, $template->effect, $code);
            Storage::disk('local')->assertExists($template->docx_path);
        }

        $this->assertSame('paid_absence', $this->template('herbi_toplanti')->effect);
        $this->assertSame('approved_order', collect($this->template('emrin_legvi')->manualFields())->firstWhere('label', 'Ləğv edilən əmr')['type']);
    }

    public function test_the_migration_registers_missing_codes_on_an_install_that_runs_the_catalogue(): void
    {
        OrderWordTemplate::query()->whereIn('code', ['donor_gunu', 'emrin_legvi'])->delete();
        $this->template('fexri_ferman')->update(['label' => 'Fəxri fərman (redaktə)']);

        $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_09_130000_register_more_order_word_templates.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, OrderWordTemplate::query()->where('code', 'donor_gunu')->count());
        $this->assertSame(1, OrderWordTemplate::query()->where('code', 'emrin_legvi')->count());
        $this->assertSame('Fəxri fərman (redaktə)', $this->template('fexri_ferman')->label);
    }

    public function test_seeded_document_only_templates_get_their_effect_and_edited_ones_are_kept(): void
    {
        // As the previous catalogue seeded them: effect none, no roles on these labels.
        foreach (StandardOrderEffectUpgrader::UPGRADES as $code => $upgrade) {
            $template = $this->template($code);
            $variables = collect($template->variables)->map(function (array $variable) use ($upgrade): array {
                if (isset($upgrade['roles'][$variable['label']]) && ! in_array($upgrade['roles'][$variable['label']], ['start_date', 'end_date'], true)) {
                    $variable['effect_role'] = null;
                }

                return $variable;
            });

            if ($code === 'evezetme') {
                // The older master: a typed extra-pay amount instead of the percent field.
                $variables = $variables->map(fn (array $v): array => $v['label'] === 'Əlavə ödəniş faizi'
                    ? [...$v, 'label' => 'Əlavə ödəniş', 'field' => ['key' => $v['token'], 'type' => 'text'], 'effect_role' => null]
                    : $v)->map(fn (array $v): array => $v['label'] === 'Əvəz edilən əməkdaş' ? [...$v, 'field' => ['key' => $v['token'], 'type' => 'text']] : $v);
            }

            $template->forceFill(['effect' => 'none', 'variables' => $variables->all()])->save();
        }

        // HR reworded the salary change in the designer: one more placeholder.
        $salary = $this->template('emek_haqqi_deyisme');
        $salary->forceFill(['variables' => [...$salary->variables, [
            'token' => 'var_99', 'label' => 'Əlavə şərt', 'source' => 'manual', 'auto_key' => null,
            'field' => ['key' => 'var_99', 'type' => 'text'], 'effect_role' => null,
        ]]])->save();

        $upgrader = app(StandardOrderEffectUpgrader::class);
        $this->assertSame(['herbi_toplanti', 'intizam_tenbehi', 'evezetme'], $upgrader->run(dryRun: true)['updated']);

        $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_09_140000_attach_effects_to_standard_order_word_templates.php');
        $migration->up();

        $this->assertSame('paid_absence', $this->template('herbi_toplanti')->effect);
        $this->assertSame('days', collect($this->template('herbi_toplanti')->variables)->firstWhere('label', 'Gün sayı')['effect_role']);
        $this->assertSame('violation', collect($this->template('intizam_tenbehi')->variables)->firstWhere('label', 'Pozuntunun təsviri')['effect_role']);
        $this->assertSame('extra_pay_amount', collect($this->template('evezetme')->variables)->firstWhere('label', 'Əlavə ödəniş')['effect_role']);
        $this->assertSame('none', $this->template('emek_haqqi_deyisme')->effect);

        $again = $upgrader->run();
        $this->assertSame([], $again['updated']);
        $this->assertSame(['emek_haqqi_deyisme'], $again['edited']);
        $this->assertContains('herbi_toplanti', $again['current']);
    }

    public function test_a_military_muster_files_a_paid_absence_and_revoking_removes_it(): void
    {
        $personnel = $this->makePersonnel();

        $order = $this->approve('herbi_toplanti', $personnel, [
            'Hərbi idarə' => 'Xətai rayon', 'Başlama tarixi' => '2026-11-02', 'Bitmə tarixi' => '2026-11-06',
            'Gün sayı' => '5', 'Toplantı yeri' => 'Bakı', 'Əsas mətni' => 'çağırış vərəqəsi',
        ]);

        $leave = Leave::query()->with('leaveType')->sole();
        $this->assertSame($personnel->tabel_no, $leave->tabel_no);
        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $leave->status_id);
        $this->assertSame('2026-11-02', $leave->starts_at->toDateString());
        $this->assertSame('2026-11-06', $leave->ends_at->toDateString());
        $this->assertSame('Hərbi toplantı', $leave->leaveType->name);
        $this->assertSame('HT', $leave->leaveType->attendance_code);
        $this->assertSame('order', $leave->submission_source);

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Test üçün geri alınır');

        $this->assertSame(0, Leave::withTrashed()->count());
        $this->assertArrayNotHasKey('absence_leave_id', (array) data_get($order->fresh()->template_snapshot, 'effect_state'));
    }

    public function test_a_donor_day_is_a_single_paid_day(): void
    {
        $personnel = $this->makePersonnel();

        $this->approve('donor_gunu', $personnel, [
            'Qanvermə tarixi' => '2026-11-02', 'İstirahət günü' => '2026-11-03', 'Əsas mətni' => 'arayış',
        ]);

        $leave = Leave::query()->with('leaveType')->sole();
        $this->assertSame('2026-11-03', $leave->starts_at->toDateString());
        $this->assertSame('2026-11-03', $leave->ends_at->toDateString());
        $this->assertSame('DG', $leave->leaveType->attendance_code);
    }

    public function test_a_paid_absence_overlapping_a_vacation_is_refused(): void
    {
        $personnel = $this->makePersonnel();
        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no, 'vacation_places' => '', 'duration' => 3,
            'start_date' => '2026-11-04', 'end_date' => '2026-11-06', 'return_work_date' => '2026-11-07',
            'order_given_by' => 'HR', 'vacation_days_total' => 0, 'remaining_days' => 0,
        ]);

        $outcome = $this->issue('secki_komissiyasi', $personnel, [
            'Seçki komissiyası' => 'MSK', 'Başlama tarixi' => '2026-11-02', 'Bitmə tarixi' => '2026-11-05',
            'Gün sayı' => '4', 'Əsas mətni' => 'məktub',
        ]);

        $this->assertFalse($outcome->isSaved());
    }

    public function test_a_disciplinary_sanction_is_recorded_for_the_configured_term_and_lifted_when_it_expires(): void
    {
        $personnel = $this->makePersonnel();
        Setting::query()->where('name', DisciplinarySanctionTerm::SETTING)->update(['value' => '6']);

        $order = $this->approve('intizam_tenbehi', $personnel, [
            'Pozuntunun təsviri' => 'Üzrsüz səbəbdən işə gəlmədiyinə görə', 'Tənbehin növü' => 'töhmət', 'Əsas mətni' => 'izahat',
        ]);

        $record = PersonnelPunishment::query()->sole();
        $this->assertSame('töhmət — Üzrsüz səbəbdən işə gəlmədiyinə görə', $record->reason);
        $given = $order->given_date->copy();
        $expires = $given->copy()->addMonthsNoOverflow(6)->toDateString();
        $this->assertSame($given->toDateString(), $record->getRawOriginal('given_date'));
        $this->assertSame($expires, $record->getRawOriginal('expired_date'));
        $this->assertSame($order->order_no, $record->order_no);

        $dayBefore = $given->copy()->addMonthsNoOverflow(6)->subDay()->toDateString();
        $this->artisan('personnel:lift-expired-sanctions', ['--date' => $dayBefore])->assertSuccessful();
        $this->assertNull($record->fresh()->getRawOriginal('lifted_at'));

        $this->artisan('personnel:lift-expired-sanctions', ['--date' => $expires])->assertSuccessful();
        $this->assertSame($expires, substr((string) $record->fresh()->getRawOriginal('lifted_at'), 0, 10));

        app(OrderStatusTransitionService::class)->cancel($order->fresh(), 'Test üçün geri alınır');
        $this->assertSame(0, PersonnelPunishment::query()->count());
    }

    public function test_a_salary_change_assigns_a_new_compensation_and_revoking_restores_the_previous_one(): void
    {
        $personnel = $this->makePersonnel();
        $regime = CompensationRegime::query()->firstOrCreate(['code' => 'private'], ['name' => 'Özəl', 'is_active' => true, 'sort' => 1]);
        $previous = EmployeeCompensation::query()->create([
            'tabel_no' => $personnel->tabel_no, 'regime_id' => $regime->id, 'base_amount' => 1000, 'currency' => 'AZN',
            'effective_from' => '2025-01-01', 'status' => 'active',
        ]);

        $order = $this->approve('emek_haqqi_deyisme', $personnel, [
            'Dəyişikliyin səbəbi' => 'attestasiyanın nəticələrini', 'Qüvvəyə minmə tarixi' => '2026-11-01',
            'Yeni əmək haqqı' => '1500', 'Əsas mətni' => 'təqdimat',
        ]);

        $current = EmployeeCompensation::query()->where('status', 'active')->sole();
        $this->assertSame('1500.00', (string) $current->base_amount);
        $this->assertSame('2026-11-01', $current->effective_from->toDateString());
        $this->assertSame('ended', $previous->fresh()->status);
        $this->assertSame('2026-10-31', $previous->fresh()->effective_to->toDateString());

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Test üçün geri alınır');

        $this->assertSame(1, EmployeeCompensation::query()->count());
        $this->assertSame('active', $previous->fresh()->status);
        $this->assertNull($previous->fresh()->effective_to);
    }

    public function test_a_recall_shortens_the_current_leave_returns_the_unused_days_and_revoking_restores_both(): void
    {
        $personnel = $this->makePersonnel();
        $this->legacyBalance($personnel, 30, 30);
        $this->approve('emek_mezuniyyeti', $personnel, [
            'İş ili' => '2026-01-01', 'Gün sayı' => '10', 'Başlama tarixi' => '2026-11-02',
            'Bitmə tarixi' => '2026-11-11', 'İşə başlama tarixi' => '2026-11-12', 'Əsas mətni' => 'ərizə',
        ]);
        $this->assertSame(20, $this->remaining($personnel));

        $recall = $this->approve('mezuniyyetden_geri_cagirma', $personnel, [
            'Səbəb' => 'istehsalat zərurəti ilə əlaqədar', 'Geri çağırma tarixi' => '2026-11-07', 'Əsas mətni' => 'razılıq ərizəsi',
        ]);

        $vacation = PersonnelVacation::query()->sole();
        $this->assertSame('2026-11-06', $vacation->getRawOriginal('end_date'));
        $this->assertSame('2026-11-07', $vacation->getRawOriginal('return_work_date'));
        $this->assertSame(5, (int) $vacation->duration);
        $this->assertSame(25, $this->remaining($personnel));
        $this->assertSame(5, (int) VacationBalanceEntry::query()->where('kind', VacationBalanceEntry::KIND_RECALL)->sum('days'));

        app(OrderStatusTransitionService::class)->revert($recall->fresh(), 'Test üçün geri alınır');

        $vacation->refresh();
        $this->assertSame('2026-11-11', $vacation->getRawOriginal('end_date'));
        $this->assertSame('2026-11-12', $vacation->getRawOriginal('return_work_date'));
        $this->assertSame(10, (int) $vacation->duration);
        $this->assertSame(20, $this->remaining($personnel));
    }

    public function test_a_recall_without_a_leave_on_that_day_is_refused(): void
    {
        $personnel = $this->makePersonnel();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('orders::order_composer.errors.recall_no_vacation', ['date' => '07.11.2026']));

        $this->approve('mezuniyyetden_geri_cagirma', $personnel, [
            'Səbəb' => 'zərurət', 'Geri çağırma tarixi' => '2026-11-07', 'Əsas mətni' => 'ərizə',
        ]);
    }

    public function test_unused_leave_compensation_takes_the_days_off_the_balance_and_revoking_gives_them_back(): void
    {
        // ƏM m.144.2: paid out when the employment contract ends.
        $personnel = $this->makePersonnel(['leave_work_date' => '2026-10-08']);
        $this->legacyBalance($personnel, 30, 30);

        $order = $this->approve('istifade_olunmamis_mezuniyyet_kompensasiyasi', $personnel, [
            'İş ili' => '2026-01-01', 'Gün sayı' => '7', 'Əsas mətni' => 'ərizə',
        ]);

        $this->assertSame(23, $this->remaining($personnel));

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Test üçün geri alınır');

        $this->assertSame(30, $this->remaining($personnel));
    }

    public function test_work_on_a_non_working_day_goes_on_record_in_attendance_and_revoking_removes_it(): void
    {
        $personnel = $this->makePersonnel();

        $order = $this->approve('qeyri_is_gunu_ise_celb', $personnel, [
            'Səbəb' => 'illik hesabatın hazırlanması', 'İş günü' => '2026-11-08',
            'Kompensasiya' => (string) OrderLookupFieldRegistry::REST_DAY_COMPENSATION_DAY_OFF, 'Əsas mətni' => 'xidməti qeyd',
        ]);

        $request = AttendanceOvertimeRequest::query()->sole();
        $this->assertSame('order', $request->source);
        $this->assertSame('approved', $request->status);
        $this->assertSame('2026-11-08', $request->date->toDateString());
        $this->assertStringContainsString(__('orders::order_composer.rest_day_compensation.day_off'), (string) $request->reason);

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Test üçün geri alınır');

        $this->assertSame(0, AttendanceOvertimeRequest::withTrashed()->count());
    }

    public function test_a_substitution_is_recorded_with_its_extra_pay_and_revoking_removes_it(): void
    {
        $personnel = $this->makePersonnel();
        $colleague = $this->makePersonnel(['surname' => 'Həsənova', 'name' => 'Leyla', 'patronymic' => 'Əli']);
        $position = Position::query()->create(['name' => 'şöbə müdiri']);

        $order = $this->approve('evezetme', $personnel, [
            'Əvəz edilən əməkdaş' => (string) $colleague->id, 'Başlama tarixi' => '2026-11-02', 'Bitmə tarixi' => '2026-11-20',
            'Əvəz edilən vəzifə' => (string) $position->id, 'Əlavə ödəniş faizi' => '30', 'Əsas mətni' => 'təqdimat',
        ]);

        $record = EmployeeSubstitution::query()->sole();
        $this->assertSame($personnel->tabel_no, $record->tabel_no);
        $this->assertSame($colleague->tabel_no, $record->substituted_tabel_no);
        $this->assertSame('Həsənova Leyla Əli', $record->substituted_name);
        $this->assertSame($position->id, (int) $record->substituted_position_id);
        $this->assertSame('30.00', (string) $record->extra_pay_percent);
        $this->assertSame('2026-11-20', $record->end_date->toDateString());

        app(OrderStatusTransitionService::class)->cancel($order->fresh(), 'Test üçün geri alınır');

        $this->assertSame(0, EmployeeSubstitution::query()->count());
    }

    public function test_an_honorary_diploma_is_recorded_without_money(): void
    {
        $personnel = $this->makePersonnel();
        AwardType::query()->create(['id' => 20, 'name' => 'mükafatlar']);
        Award::query()->create(['id' => 2001, 'award_type_id' => 20, 'name' => 'Xidmətdə fərqləndiyinə görə']);

        $order = $this->approve('fexri_ferman', $personnel, [
            'Təltifin səbəbi' => 'uzunmüddətli səmərəli fəaliyyətinə görə', 'Əsas mətni' => 'təqdimat',
        ]);

        $award = PersonnelAward::query()->sole();
        $this->assertNull($award->amount);
        $this->assertSame('Fəxri fərmanla təltif — uzunmüddətli səmərəli fəaliyyətinə görə', $award->reason);

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Test üçün geri alınır');
        $this->assertSame(0, PersonnelAward::query()->count());
    }

    public function test_an_order_revocation_cancels_the_target_and_revoking_it_re_approves_the_target(): void
    {
        $personnel = $this->makePersonnel();
        $trip = $this->approve('ezamiyyet', $personnel, $this->tripFields());
        $this->assertSame(1, PersonnelBusinessTrip::query()->count());

        $options = app(OrderLookupFieldRegistry::class)->options('approved_order', ['personnel_id' => $personnel->id]);
        $this->assertSame([$trip->id], array_column($options, 'id'));

        $revocation = $this->approve('emrin_legvi', $personnel, [
            'Səbəb' => 'ezamiyyətin təxirə salınmasını', 'Ləğv edilən əmr' => (string) $trip->id, 'Əsas mətni' => 'xidməti qeyd',
        ]);

        $trip->refresh();
        $this->assertSame(OrderStatusEnum::CANCELLED->value, (int) $trip->status_id);
        $this->assertSame($revocation->id, (int) data_get($trip->template_snapshot, 'cancelled_by_order_id'));
        $this->assertSame(0, PersonnelBusinessTrip::withTrashed()->count());
        $this->assertTrue(\Spatie\Activitylog\Models\Activity::query()
            ->where('subject_id', $trip->id)->where('event', 'cancelled')
            ->where('properties->reason', __('orders::order_composer.cancellation_reason', ['number' => $revocation->order_no]))
            ->exists());

        app(OrderStatusTransitionService::class)->revert($revocation->fresh(), 'Ləğv əmri səhv verilib');

        $trip->refresh();
        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $trip->status_id);
        $this->assertNull(data_get($trip->template_snapshot, 'cancelled_by_order_id'));
        $this->assertSame(1, PersonnelBusinessTrip::query()->count());
    }

    public function test_an_order_revocation_refuses_another_employees_order_and_chains(): void
    {
        $personnel = $this->makePersonnel();
        $other = $this->makePersonnel();
        $foreign = $this->approve('ezamiyyet', $other, $this->tripFields());

        try {
            $this->approve('emrin_legvi', $personnel, ['Səbəb' => 'x', 'Ləğv edilən əmr' => (string) $foreign->id, 'Əsas mətni' => 'y']);
            $this->fail('Another employee\'s order must not be revoked.');
        } catch (DomainException $exception) {
            $this->assertSame(__('orders::order_composer.errors.cancellation_other_employee', ['number' => $foreign->order_no]), $exception->getMessage());
        }
        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $foreign->fresh()->status_id);

        $own = $this->approve('ezamiyyet', $personnel, [...$this->tripFields(), 'Başlama tarixi' => '2026-12-01', 'Bitmə tarixi' => '2026-12-03', 'İşə başlama tarixi' => '2026-12-04']);
        $first = $this->approve('emrin_legvi', $personnel, ['Səbəb' => 'x', 'Ləğv edilən əmr' => (string) $own->id, 'Əsas mətni' => 'y']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('orders::order_composer.errors.cancellation_chain', ['number' => $first->order_no]));

        $this->approve('emrin_legvi', $personnel, ['Səbəb' => 'x', 'Ləğv edilən əmr' => (string) $first->id, 'Əsas mətni' => 'y']);
    }

    public function test_the_remaining_new_types_are_issued_as_documents(): void
    {
        $personnel = $this->makePersonnel();
        $position = Position::query()->create(['name' => 'mühəndis']);

        $this->approve('hevale', $personnel, [
            'Başlama tarixi' => '2026-11-02', 'Bitmə tarixi' => '2026-11-30', 'Həvalə edilən vəzifə' => (string) $position->id, 'Əsas mətni' => 'təqdimat',
        ]);
        $this->approve('qisaldilmis_is_vaxti', $personnel, [
            'Səbəb' => 'bir yaşınadək uşağı olduğuna görə', 'Başlama tarixi' => '2026-11-02',
            'Güzəştin təsviri' => 'hər üç saatdan bir 30 dəqiqəlik əlavə fasilə', 'Əsas mətni' => 'ərizə',
        ]);
        $this->approve('mezuniyyetin_kecirilmesi', $personnel, [
            'İş ili' => '2026-01-01', 'Əvvəlki başlama tarixi' => '2026-11-02', 'Səbəb' => 'əməkdaşın xəstələnməsi ilə əlaqədar',
            'Yeni başlama tarixi' => '2026-12-01', 'Yeni bitmə tarixi' => '2026-12-21', 'Əsas mətni' => 'ərizə',
        ]);
        $this->approve('usaga_qulluq_mezuniyyeti', $personnel, [
            'Uşaq haqqında məlumat' => '2026-cı il təvəllüdlü', 'Başlama tarixi' => '2027-01-01',
            'Bitmə tarixi' => '2027-12-31', 'İşə başlama tarixi' => '2028-01-01', 'Əsas mətni' => 'ərizə',
        ]);

        $this->assertSame(4, OrderLog::query()->where('status_id', OrderStatusEnum::APPROVED->value)->count());
        // Child-care leave is social leave: on record, the annual balance untouched.
        $this->assertSame(1, PersonnelVacation::query()->count());
        $this->assertSame(0, Vacation::query()->count());
    }

    /**
     * @return array<string,string>
     */
    private function tripFields(): array
    {
        return [
            'Ezamiyyətin məqsədi' => 'təlimdə iştirak', 'Başlama tarixi' => '2026-11-02', 'Bitmə tarixi' => '2026-11-05',
            'Ezamiyyə yeri' => 'Gəncə şəhəri', 'Nəqliyyat' => 'qatar', 'İşə başlama tarixi' => '2026-11-06', 'Əsas mətni' => 'xidməti qeyd',
        ];
    }

    /**
     * @param  array<string,string>  $fieldsByLabel
     */
    private function approve(string $code, Personnel $personnel, array $fieldsByLabel): OrderLog
    {
        $outcome = $this->issue($code, $personnel, $fieldsByLabel);
        $this->assertTrue($outcome->isSaved(), $code.': '.json_encode($outcome->errors, JSON_UNESCAPED_UNICODE).' '.$outcome->message);

        $order = OrderLog::query()->latest('id')->firstOrFail();
        app(OrderStatusTransitionService::class)->approve($order);

        return $order->fresh();
    }

    /**
     * @param  array<string,string>  $fieldsByLabel
     */
    private function issue(string $code, Personnel $personnel, array $fieldsByLabel)
    {
        $template = $this->template($code);
        $fields = [];
        foreach ($fieldsByLabel as $label => $value) {
            $fields[(string) collect($template->manualFields())->firstWhere('label', $label)['key']] = $value;
        }

        return app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
            $code, $personnel->id, null, null, null, $fields, ($this->number++).'-M', '08.10.2026', 'Bakı şəhəri',
        ), false);
    }

    /**
     * A calendar-year balance as the previous ledger kept it, moved into the work-year
     * ledger the way the upgrade migration does.
     */
    private function legacyBalance(Personnel $personnel, int $total, int $remaining): void
    {
        Vacation::query()->create([
            'tabel_no' => $personnel->tabel_no, 'year' => 2026, 'reserved_date_month' => null,
            'vacation_days_total' => $total, 'remaining_days' => $remaining,
        ]);

        app(LegacyVacationMigrator::class)->migrate();
    }

    private function remaining(Personnel $personnel): int
    {
        return app(VacationBalanceService::class)->balanceOn($personnel->fresh(), CarbonImmutable::parse('2026-12-31'))['remaining'];
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
