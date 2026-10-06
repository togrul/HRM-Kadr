<?php

namespace Tests\Feature\Integration;

use App\Models\ApiToken;
use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\CompensationComponent;
use App\Models\CompensationRegime;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\EmployeeCompensation;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Integration\Support\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The integration API served to the finance system.
 *
 * Two things are being protected here. First, that only what payroll needs
 * crosses the boundary: the personnel record also holds disciplinary notes,
 * medical results and war-participation flags, and none of that is any of the
 * accounting department's business. Second, that a token is scoped — one issued
 * for the org tree must not read salaries.
 */
class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('integration-ip:127.0.0.1');
        $this->seedReferenceData();
    }

    public function test_the_handshake_describes_this_system(): void
    {
        $response = $this->withToken($this->issue())->getJson('/api/v1/handshake');

        $response->assertOk()
            ->assertJsonPath('data.system', Contract::SYSTEM)
            ->assertJsonPath('data.contract_version', Contract::VERSION)
            ->assertJsonPath('data.supports_person_uid', true);

        $this->assertContains(Contract::EMPLOYEES, $response->json('data.feeds'));
    }

    public function test_the_handshake_refuses_an_unknown_token(): void
    {
        $this->withToken('hrm_not-a-real-token')->getJson('/api/v1/handshake')->assertUnauthorized();
    }

    public function test_the_handshake_refuses_a_missing_token(): void
    {
        $this->getJson('/api/v1/handshake')->assertUnauthorized();
    }

    public function test_an_expired_token_is_refused(): void
    {
        $issued = ApiToken::generate('Köhnə', null, now()->subDay());

        $this->withToken($issued['plain'])->getJson('/api/v1/handshake')->assertUnauthorized();
    }

    /** A token issued for the org tree must not read people. */
    public function test_an_ability_is_enforced_per_feed(): void
    {
        $orgOnly = ApiToken::generate('Yalnız struktur', [Contract::ABILITY_ORG]);

        $this->withToken($orgOnly['plain'])->getJson('/api/v1/employees')->assertForbidden();
        $this->withToken($orgOnly['plain'])->getJson('/api/v1/handshake')->assertOk();
    }

    public function test_the_feed_returns_the_whitelisted_fields(): void
    {
        $person = $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');

        // The module seeds its own regimes; reuse whichever is there.
        $regime = CompensationRegime::query()->firstOrCreate(
            ['code' => 'private'],
            ['name' => 'Özəl', 'is_active' => true],
        );

        EmployeeCompensation::query()->create([
            'tabel_no' => 'TB-1',
            'regime_id' => $regime->id,
            'base_amount' => 1800,
            'currency' => 'AZN',
            'effective_from' => '2020-01-01',
            'status' => 'active',
        ]);

        $row = $this->withToken($this->issue())->getJson('/api/v1/employees')
            ->assertOk()
            ->json('data.items.0');

        $this->assertSame((string) $person->id, $row['external_id']);
        $this->assertSame($person->person_uid, $row['person_uid']);
        $this->assertSame('TB-1', $row['external_no']);
        $this->assertSame('Əliyev', $row['last_name']);
        $this->assertSame('Elçin', $row['first_name']);
        $this->assertSame('active', $row['status']);
        $this->assertEqualsWithDelta(1800.0, $row['base_salary'], 0.001);
    }

    /**
     * Nothing beyond the contract crosses.
     *
     * This is the whole point of naming fields explicitly: a column added to
     * `personnels` tomorrow must not travel with the next sync.
     */
    public function test_the_feed_leaks_no_extra_personnel_fields(): void
    {
        $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');

        $row = $this->withToken($this->issue())->getJson('/api/v1/employees')->json('data.items.0');

        foreach (['residental_address', 'discrediting_information', 'medical_inspection_result',
            'participation_in_war', 'extra_important_information', 'added_by', 'id'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $row, "«{$forbidden}» sahəsi məftilə düşməməlidir.");
        }

        $this->assertEqualsCanonicalizing(
            ['external_id', 'person_uid', 'external_no', 'last_name', 'first_name', 'patronymic',
                'birth_date', 'gender', 'fin', 'phone', 'email', 'department_code', 'position_code',
                'grade', 'category_code', 'hire_date', 'dismiss_date', 'status', 'base_salary',
                'work_schedule_code'],
            array_keys($row),
        );
    }

    /** The cursor walks forward by id and reports whether more remains. */
    public function test_the_feed_pages_by_cursor(): void
    {
        $first = $this->makePersonnel('TB-1', 'Bir', 'Bir');
        $second = $this->makePersonnel('TB-2', 'İki', 'İki');

        $page = $this->withToken($this->issue())->getJson('/api/v1/employees?limit=1')->json('data');

        $this->assertCount(1, $page['items']);
        $this->assertTrue($page['has_more']);
        $this->assertSame($first->id, $page['last_sequence']);

        $next = $this->withToken($this->issue())
            ->getJson('/api/v1/employees?limit=1&after='.$page['last_sequence'])->json('data');

        $this->assertSame((string) $second->id, $next['items'][0]['external_id']);
        $this->assertFalse($next['has_more']);
    }

    public function test_a_bad_cursor_is_rejected(): void
    {
        $this->withToken($this->issue())->getJson('/api/v1/employees?after=-5')->assertStatus(422);
        $this->withToken($this->issue())->getJson('/api/v1/employees?limit=99999')->assertStatus(422);
    }

    /** A dismissed person still travels — final settlement needs them. */
    public function test_a_dismissed_person_is_still_reported(): void
    {
        $person = $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');
        $person->forceFill(['leave_work_date' => '2026-05-31'])->save();

        $row = $this->withToken($this->issue())->getJson('/api/v1/employees')->json('data.items.0');

        $this->assertSame('dismissed', $row['status']);
        $this->assertSame('2026-05-31', $row['dismiss_date']);
    }

    /**
     * The tree travels as (code, parent_code), never as our ids.
     *
     * That is what lets the two schemas stay independent: the far side rebuilds
     * the hierarchy without ever storing a key that belongs to us.
     */
    public function test_the_org_tree_travels_by_code(): void
    {
        Structure::query()->create([
            'id' => 501, 'name' => 'Backend', 'shortname' => 'BE',
            'parent_id' => 500, 'coefficient' => 1, 'code' => 501, 'level' => 2,
        ]);

        $items = collect($this->withToken($this->issue())->getJson('/api/v1/org.units')
            ->assertOk()->json('data.items'))->keyBy('code');

        $this->assertNull($items['500']['parent_code']);
        $this->assertSame('500', $items['501']['parent_code']);
        $this->assertSame('Backend', $items['501']['name']);
    }

    /**
     * Duplicate `structures.code` values must not collapse the tree.
     *
     * This is what real data looks like: `code` is a sibling ordinal, not a
     * business code, so a 43-unit organisation carries codes 1, 1, 1, 2, 1 …
     * repeated at every level, and nothing in the schema forbids it. Sending
     * that column broke the consumer twice over — its link table rejected the
     * duplicates outright, and `parent_code` became ambiguous, so a rebuilt
     * tree would have been silently wrong.
     *
     * The test above passed throughout because it set `code` equal to `id`.
     */
    public function test_duplicate_sibling_codes_still_rebuild_one_tree(): void
    {
        Structure::query()->create([
            'id' => 601, 'name' => 'Satış', 'shortname' => 'S',
            'parent_id' => 500, 'coefficient' => 1, 'code' => 1, 'level' => 2,
        ]);
        Structure::query()->create([
            'id' => 602, 'name' => 'Marketinq', 'shortname' => 'M',
            'parent_id' => 500, 'coefficient' => 1, 'code' => 1, 'level' => 2,
        ]);
        Structure::query()->create([
            'id' => 603, 'name' => 'Onlayn satış', 'shortname' => 'OS',
            'parent_id' => 601, 'coefficient' => 1, 'code' => 1, 'level' => 3,
        ]);

        $items = collect($this->withToken($this->issue())->getJson('/api/v1/org.units')
            ->assertOk()->json('data.items'));

        $codes = $items->pluck('code');

        $this->assertSame($codes->count(), $codes->unique()->count(), 'Kodlar unikal olmalıdır.');

        $byCode = $items->keyBy('code');

        // Two siblings sharing `code = 1` stay two distinct units...
        $this->assertSame('Satış', $byCode['601']['name']);
        $this->assertSame('Marketinq', $byCode['602']['name']);

        // ...and the grandchild points at its own parent, not at the other one.
        $this->assertSame('601', $byCode['603']['parent_code']);
        $this->assertSame('500', $byCode['601']['parent_code']);
    }

    public function test_positions_are_served(): void
    {
        $row = $this->withToken($this->issue())->getJson('/api/v1/org.positions')
            ->assertOk()->json('data.items.0');

        $this->assertSame('500', $row['code']);
        $this->assertSame('Proqramçı', $row['name']);
    }

    /** The org token reads the org, and stops there. */
    public function test_the_org_ability_does_not_unlock_people(): void
    {
        $orgOnly = ApiToken::generate('Yalnız struktur', [Contract::ABILITY_ORG]);

        $this->withToken($orgOnly['plain'])->getJson('/api/v1/org.units')->assertOk();
        $this->withToken($orgOnly['plain'])->getJson('/api/v1/employees')->assertForbidden();
    }

    /**
     * Everything the handshake advertises is actually served.
     *
     * A handshake that promises a feed which 404s would let the counterpart
     * connect happily and fail later, far from the setup screen where the
     * mistake was made.
     */
    public function test_every_advertised_feed_answers(): void
    {
        $token = $this->issue();

        foreach ($this->withToken($token)->getJson('/api/v1/handshake')->json('data.feeds') as $feed) {
            // Davamiyyət dövr tələb edir: ay payroll-un iş vahididir və
            // «hamısını ver» sorğusu bütün ledger tarixçəsini axıdardı.
            $query = $feed === Contract::ATTENDANCE_MONTH ? '?year=2026&month=7' : '';

            $this->withToken($token)->getJson('/api/v1/'.$feed.$query)
                ->assertOk("«{$feed}» elan olunub, amma cavab vermir.");
        }
    }

    /** Davamiyyət günləri xam faktla gəlir — tərcümə oxuyanın işidir. */
    public function test_attendance_ships_raw_facts_not_day_codes(): void
    {
        $person = $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');

        AttendanceDailyLedger::query()->create([
            'tabel_no' => 'TB-1',
            'date' => '2026-07-06',
            'scheduled_minutes' => 480,
            'worked_minutes' => 480,
            'break_minutes' => 0,
            'overtime_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
        ]);

        $row = $this->withToken($this->issue())
            ->getJson('/api/v1/attendance.month?year=2026&month=7')
            ->assertOk()->json('data.items.0');

        $this->assertSame((string) $person->id, $row['external_id']);
        $this->assertSame(6, $row['days'][0]['day']);
        $this->assertSame('present', $row['days'][0]['status']);
        $this->assertSame(480, $row['days'][0]['worked_minutes']);
        $this->assertArrayNotHasKey('time_code', $row['days'][0], 'Tərcümə bu tərəfdə edilmir.');
    }

    /**
     * Təqvim barmaq izi məzmuna bağlıdır, sətir sırasına yox.
     *
     * Norma günü hansı tarixlərin bayram olduğundan asılıdır. İki sistem bunda
     * fərqlənsə, hər norma və hər maaş səssizcə sürüşər.
     */
    public function test_the_calendar_hash_depends_on_content(): void
    {
        $before = $this->withToken($this->issue())
            ->getJson('/api/v1/attendance.month?year=2026&month=7')->json('data.calendar_hash');

        AttendanceCalendar::query()->create([
            'date' => '2026-07-15', 'day_type' => 'holiday', 'name' => 'Test bayramı',
            'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
        ]);

        $after = $this->withToken($this->issue())
            ->getJson('/api/v1/attendance.month?year=2026&month=7')->json('data.calendar_hash');

        $this->assertNotSame($before, $after, 'Təqvim dəyişəndə barmaq izi də dəyişməlidir.');
    }

    /** Dövr tələb olunur — «hamısını ver» sorğusu qəbul edilmir. */
    public function test_attendance_requires_a_period(): void
    {
        $this->withToken($this->issue())->getJson('/api/v1/attendance.month')->assertStatus(422);
    }

    /**
     * Statutory components NEVER cross the boundary.
     *
     * The catalogue here also holds income tax, pension and the rest. Sending
     * them would mean two systems each keeping a rate table for the same tax,
     * and when the law changed one of them would quietly be wrong. The payroll
     * side computes those from its own tables — that is where the legal engine
     * is.
     */
    public function test_statutory_components_never_leave(): void
    {
        $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');
        $regime = $this->regime();

        $allowance = CompensationComponent::query()->firstOrCreate(['code' => 'lang'], ['name' => 'Dil əlavəsi', 'type' => 'earning',
            'calc_type' => 'fixed', 'taxable' => true, 'affects_social' => true,
            'is_statutory' => false, 'is_active' => true,
        ]);

        // Modul öz statutory komponentlərini onsuz da seed edir.
        $tax = CompensationComponent::query()->firstOrCreate(['code' => 'income_tax'], ['name' => 'Gəlir vergisi', 'type' => 'deduction',
            'calc_type' => 'bracket', 'taxable' => false, 'affects_social' => false,
            'is_statutory' => true, 'is_active' => true,
        ]);

        $compensation = EmployeeCompensation::query()->create([
            'tabel_no' => 'TB-1', 'regime_id' => $regime->id, 'base_amount' => 1000,
            'currency' => 'AZN', 'effective_from' => '2020-01-01', 'status' => 'active',
        ]);

        $compensation->lines()->create(['component_id' => $allowance->id, 'amount' => 100]);
        $compensation->lines()->create(['component_id' => $tax->id, 'amount' => 140]);

        $row = $this->withToken($this->issue())->getJson('/api/v1/compensation')
            ->assertOk()->json('data.items.0');

        $codes = array_column($row['components'], 'code');

        $this->assertContains('lang', $codes, 'Kadr qərarı olan əlavə keçməlidir.');
        $this->assertNotContains('income_tax', $codes, 'Vergi bu sərhəddən keçməməlidir.');
        $this->assertEqualsWithDelta(1000.0, $row['base_amount'], 0.001);
    }

    /** Kompensasiyası olmayan şəxs feed-də görünmür — göndəriləcək şərt yoxdur. */
    public function test_people_without_compensation_are_omitted(): void
    {
        $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');

        $response = $this->withToken($this->issue())->getJson('/api/v1/compensation')->assertOk();

        $this->assertSame([], $response->json('data.items') ?? []);
    }

    /**
     * Məzuniyyət balansı verilir — son haqq-hesab üçün.
     *
     * İşdən azad olanda istifadə edilməmiş məzuniyyət ödənilir (Əmək Məcəlləsi
     * Md. 144). Ödənişi maliyyə hesablayır, amma gün sayı kadr faktıdır və
     * burada saxlanılır.
     */
    public function test_the_leave_balance_is_published(): void
    {
        $person = $this->makePersonnel('TB-1', 'Əliyev', 'Elçin');

        $row = $this->withToken($this->issue())->getJson('/api/v1/leave.balance?year=2026')
            ->assertOk()->json('data.items.0');

        $this->assertSame((string) $person->id, $row['external_id']);
        $this->assertSame(2026, $row['year']);
        $this->assertArrayHasKey('remaining_days', $row);
        $this->assertArrayHasKey('used_days', $row);
    }

    /** Kompensasiya feed-inin öz icazəsi var. */
    public function test_the_compensation_feed_is_scoped(): void
    {
        $orgOnly = ApiToken::generate('Struktur', [Contract::ABILITY_ORG]);

        $this->withToken($orgOnly['plain'])->getJson('/api/v1/compensation')->assertForbidden();
    }

    // ---------------------------------------------------------------- helpers

    private function regime(): CompensationRegime
    {
        return CompensationRegime::query()->firstOrCreate(
            ['code' => 'private'],
            ['name' => 'Özəl', 'is_active' => true],
        );
    }

    private function issue(): string
    {
        return ApiToken::generate('ARBAY test', null)['plain'];
    }

    private function seedReferenceData(): void
    {
        // The personnel observer notifies admins on hire; without the role the
        // notification lookup throws and hides whatever we are actually testing.
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Country::query()->create(['id' => 1, 'code' => 'AZ']);
        EducationDegree::query()->create([
            'id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bakalavr',
        ]);
        WorkNorm::query()->create([
            'id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Polniy',
        ]);
        Structure::query()->create([
            'id' => 500, 'name' => 'İnformasiya texnologiyaları', 'shortname' => 'IT',
            'parent_id' => null, 'coefficient' => 1, 'code' => 500, 'level' => 1,
        ]);
        Position::query()->create([
            'id' => 500, 'name' => 'Proqramçı', 'approval_rank' => 0, 'is_approval_target' => true,
        ]);
    }

    private function makePersonnel(string $tabelNo, string $surname, string $name): Personnel
    {
        $user = User::factory()->create();

        return Personnel::query()->create([
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => $name,
            'patronymic' => 'Rəşad oğlu',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => '5AB1CD'.substr($tabelNo, -1),
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'structure_id' => 500,
            'position_id' => 500,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => $user->id,
            'is_pending' => false,
        ]);
    }
}
