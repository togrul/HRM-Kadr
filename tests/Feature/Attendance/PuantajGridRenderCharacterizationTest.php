<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Livewire\PuantajGrid;
use App\Support\Livewire\LivewireComponentProfiler;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Locks what every puantaj cell shows — classes, native title, visible text, icon markup
 * and the click-detail popover (label + lines) — for a month holding every status, leave
 * shape, calendar marker and overtime variant. The golden file was captured from the
 * pre-optimisation view, so any rendering drift fails here.
 */
class PuantajGridRenderCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private const GOLDEN = __DIR__.'/../../Fixtures/Attendance/puantaj-grid-cells.json';

    public function test_every_cell_variant_renders_exactly_as_the_golden_snapshot(): void
    {
        $this->actingAs($this->authorizedUser());
        self::seedMonthFixture(4);

        $html = Livewire::test(PuantajGrid::class, ['year' => 2026, 'month' => 3])->html();
        $cells = self::extractCells($html);

        if (getenv('PUANTAJ_WRITE_GOLDEN') === '1') {
            file_put_contents(self::GOLDEN, json_encode($cells, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        }

        $this->assertCount(4 * 31, array_merge(...array_values($cells)));
        $this->assertSame(json_decode((string) file_get_contents(self::GOLDEN), true), $cells);
    }

    public function test_cells_carry_no_per_cell_alpine_payload_or_dynamic_component_markup(): void
    {
        $this->actingAs($this->authorizedUser());
        self::seedMonthFixture(4);

        $html = Livewire::test(PuantajGrid::class, ['year' => 2026, 'month' => 3])->html();

        $this->assertStringNotContainsString('openDetail($event', $html);
        $this->assertStringContainsString('x-data="puantajGrid()"', $html);
        $this->assertStringContainsString('data-month="03.2026"', $html);
    }

    public function test_a_full_twenty_row_month_stays_within_the_render_payload_budget(): void
    {
        $user = $this->authorizedUser();
        self::seedMonthFixture(20);

        $metrics = app(LivewireComponentProfiler::class)->measureRender($user, PuantajGrid::class, ['year' => 2026, 'month' => 3]);

        // Was ~1.16MB before cell payloads/icons were deduplicated; now ~0.5MB.
        $this->assertLessThanOrEqual(
            (int) config('attendance.performance.render_budget.puantaj_grid_render.response_bytes'),
            (int) $metrics['response_bytes']
        );
    }

    /**
     * One row per personnel, keyed by row index: each cell is normalised to what a user
     * can see or trigger.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function extractCells(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $sprites = [];
        foreach ($xpath->query('//symbol[@id]') as $symbol) {
            /** @var DOMElement $symbol */
            $icon = $xpath->query('./svg', $symbol)->item(0);
            $sprites['<use href="#'.$symbol->getAttribute('id').'"></use>'] = self::normalise(self::innerHtml($dom, $icon));
        }
        $resolve = fn (string $html): string => strtr(self::normalise($html), $sprites);

        $monthNode = $xpath->query('//*[@data-month]')->item(0);
        $monthSuffix = $monthNode instanceof DOMElement ? $monthNode->getAttribute('data-month') : '';

        $rows = [];
        foreach ($xpath->query('//tbody/tr') as $rowIndex => $tr) {
            /** @var DOMElement $tr */
            $cells = [];
            foreach ($xpath->query('./td[button]', $tr) as $td) {
                /** @var DOMElement $td */
                $button = $xpath->query('./button', $td)->item(0);
                $svgs = [];
                foreach ($xpath->query('.//svg', $td) as $svg) {
                    $svgs[] = $resolve($dom->saveHTML($svg));
                }

                $cells[] = [
                    'class' => self::normalise($td->getAttribute('class')),
                    'title' => $td->getAttribute('title'),
                    'text' => self::normalise($td->textContent),
                    'markup' => $resolve(self::innerHtml($dom, $button)),
                    'svgs' => $svgs,
                    'detail' => self::detailOf($button, $tr, $td, $monthSuffix),
                ];
            }

            $rows[$rowIndex] = $cells;
        }

        return $rows;
    }

    /**
     * Mirrors resources/js/puantaj-grid.js: what the popover shows when the cell is clicked.
     *
     * @return array{label: string, lines: array<int, string>}|null
     */
    private static function detailOf(DOMElement $button, DOMElement $tr, DOMElement $td, string $monthSuffix): ?array
    {
        if (! $button->hasAttribute('data-d')) {
            return null;
        }

        $day = str_pad($button->getAttribute('data-d'), 2, '0', STR_PAD_LEFT);
        $lines = $button->hasAttribute('data-lines')
            ? json_decode($button->getAttribute('data-lines'), true)
            : explode(' | ', $td->getAttribute('title'));

        return [
            'label' => $tr->getAttribute('data-name').' • '.$day.'.'.$monthSuffix,
            'lines' => $lines,
        ];
    }

    private static function innerHtml(DOMDocument $dom, DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return $html;
    }

    private static function normalise(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', preg_replace('/>\s+</u', '><', $value)));
    }

    /**
     * Seeds $rows personnel for March 2026, cycling every cell variant across the month.
     */
    public static function seedMonthFixture(int $rows): void
    {
        $user = User::query()->first() ?? User::factory()->create();
        $country = Country::query()->first() ?? Country::query()->create(['id' => 1, 'code' => 'AZ']);
        EducationDegree::query()->firstOrCreate(['id' => 1], ['title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bakalavr']);
        WorkNorm::query()->firstOrCreate(['id' => 1], ['name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Polniy']);
        $structure = Structure::query()->first() ?? Structure::query()->create([
            'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1,
        ]);
        $position = Position::query()->first() ?? Position::query()->create(['id' => 1, 'name' => 'Officer']);

        AttendanceCalendar::query()->create([
            'date' => '2026-03-09', 'day_type' => 'holiday', 'name' => 'attendance::calendar_regimes.options.holiday',
            'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
        ]);
        AttendanceCalendar::query()->create([
            'date' => '2026-03-14', 'day_type' => 'workday', 'name' => '',
            'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
        ]);

        $names = [
            ['Doe', 'John', 'Smith'],
            ['Əliyev', 'Şəhla', ''],
            ["O'Brien", 'Ann "Q"', 'Lee'],
            ['Brown <b>', 'Tom & Co', 'Ray'],
        ];

        $variants = self::variants();
        $ledgers = [];

        for ($r = 0; $r < $rows; $r++) {
            [$surname, $name, $patronymic] = $names[$r % count($names)];
            $tabelNo = sprintf('TB%05d', $r + 1);

            Personnel::withoutEvents(fn () => Personnel::query()->create([
                'tabel_no' => $tabelNo,
                'surname' => $surname.($r >= count($names) ? ' '.$r : ''),
                'name' => $name,
                'patronymic' => $patronymic,
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'mobile' => '994501112233',
                'nationality_id' => $country->id,
                'pin' => sprintf('P%06d', $r + 1),
                'residental_address' => 'Main st',
                'education_degree_id' => 1,
                'structure_id' => $structure->id,
                'position_id' => $position->id,
                'work_norm_id' => 1,
                'join_work_date' => '2026-01-01',
                'added_by' => $user->id,
                'is_pending' => false,
            ]));

            for ($day = 1; $day <= 31; $day++) {
                $variant = $variants[($day - 1 + $r * 7) % count($variants)];
                if ($variant === null) {
                    continue;
                }

                $ledgers[] = [
                    'tabel_no' => $tabelNo,
                    'date' => sprintf('2026-03-%02d', $day),
                    'scheduled_minutes' => 540,
                    'worked_minutes' => $variant[1],
                    'break_minutes' => 0,
                    'overtime_minutes' => 0,
                    'late_minutes' => 0,
                    'early_leave_minutes' => 0,
                    'attendance_status' => $variant[0],
                    'absence_code' => $variant[2],
                    'source_summary' => 'fixture',
                    'is_locked' => false,
                    'meta' => json_encode($variant[3]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($ledgers, 200) as $chunk) {
            AttendanceDailyLedger::query()->insert($chunk);
        }
    }

    /**
     * [status, worked_minutes, absence_code, meta] — null means "no ledger row".
     *
     * @return array<int, array{0: string, 1: int, 2: ?string, 3: array<string, mixed>}|null>
     */
    private static function variants(): array
    {
        return [
            null,
            ['present', 540, null, []],
            ['present', 600, null, []],
            ['present', 300, null, []],
            ['manual_present', 540, null, []],
            ['holiday_worked', 480, null, ['calendar_day_type' => 'holiday']],
            ['weekend_worked', 240, null, ['calendar_day_type' => 'weekend']],
            ['absent', 0, 'X', []],
            ['absent', 0, null, []],
            ['manual_absence', 0, 'MA', []],
            ['weekend', 0, null, ['calendar_day_type' => 'weekend']],
            ['holiday', 0, null, ['calendar_day_type' => 'holiday']],
            ['vacation', 0, null, []],
            ['business_trip', 0, null, []],
            ['leave', 0, null, ['leave_type_id' => 5, 'leave_type_name' => 'Saatlıq icazə', 'leave_type_code' => 'SI']],
            ['leave', 0, 'LEAVE', []],
            ['leave', 0, null, ['leave_type_id' => 7, 'leave_type_name' => 'Ailə vəziyyəti ilə']],
            ['leave', 0, null, ['leave_type_id' => 3, 'leave_type_name' => 'Təhsil məzuniyyəti', 'leave_type_code' => 'tm']],
            ['present', 355, null, [
                'leave_type_id' => 5, 'leave_type_name' => 'Saatlıq icazə', 'leave_type_code' => 'SI', 'duration_unit' => 'hour',
                'starts_time' => '09:00:00', 'ends_time' => '11:00:00', 'total_minutes' => 120, 'covered_leave_minutes' => 120,
            ]],
            ['present', 270, 'LEAVE', [
                'leave_type_name' => 'Şəxsi iş', 'duration_unit' => 'half_day', 'partial_day_part' => 'first_half', 'covered_leave_minutes' => 270,
            ]],
            ['absent', 0, null, [
                'leave_type_id' => 9, 'leave_type_name' => 'Yarım gün', 'duration_unit' => 'half_day', 'partial_day_part' => 'second_half',
            ]],
            ['leave', 0, null, ['leave_type_id' => 11, 'leave_type_name' => "A | B <x> \"q\" O'N & c"]],
            ['present', 540, null, ['calendar_day_type' => 'workday']],
            ['late', 0, null, []],
            ['leave', 0, null, ['leave_type_id' => 12, 'leave_type_name' => 'Saat', 'duration_unit' => 'hour', 'starts_time' => '14:00', 'ends_time' => '16:30']],
            ['business_trip', 0, null, ['calendar_day_type' => 'holiday']],
            ['vacation', 0, null, ['calendar_day_type' => 'weekend']],
            ['present', 480, null, []],
            ['present', 45, 'LEAVE', ['duration_unit' => 'hour', 'leave_type_name' => 'Qısa', 'total_minutes' => 90, 'starts_time' => '10:00', 'ends_time' => '11:30']],
            ['leave', 0, 'LEAVE', ['leave_type_name' => 'Xəstəlik']],
            ['present', 541, null, []],
        ];
    }

    private function authorizedUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'Puantaj Snapshot User', 'guard_name' => 'web']);
        $role->syncPermissions([Permission::findOrCreate('show-attendance', 'web')]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
