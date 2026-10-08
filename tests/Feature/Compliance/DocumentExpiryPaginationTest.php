<?php

namespace Tests\Feature\Compliance;

use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The dashboard is one SQL union paged by the database. The expected values below were
 * captured from the previous in-memory (PHP) implementation on the same fixture.
 */
class DocumentExpiryPaginationTest extends TestCase
{
    use RefreshDatabase;

    private const STRUCTURE_SCORES = [
        ['structure_name' => 'Beta Unit', 'total' => 36, 'missing' => 26, 'expired' => 2, 'at_risk' => 31, 'score' => 22],
        ['structure_name' => 'Təyin edilməyib', 'total' => 37, 'missing' => 25, 'expired' => 4, 'at_risk' => 32, 'score' => 22],
        ['structure_name' => 'Alpha HQ', 'total' => 40, 'missing' => 17, 'expired' => 5, 'at_risk' => 29, 'score' => 45],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-30 10:00:00');
        app()->setLocale('az');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_later_page_holds_the_right_missing_and_document_rows(): void
    {
        DocumentExpiryFixture::seedMixed();

        $rows = app(DocumentExpiryReadService::class)->dashboard([], 3)['rows'];

        $this->assertSame(113, $rows->total());
        $this->assertSame(3, $rows->currentPage());
        $this->assertSame([
            // The passport requirement is labelled "Şəxsiyyət sənədi (...)", which sorts after
            // "Tibbi arayış" and "Xidməti vəsiqə" in byte order.
            'P000030|medical|missing', 'P000005|medical|missing', 'P000005|service_card|missing',
            'P000005|passport|missing', 'P000005|contract|missing', 'P000006|medical|missing',
            'P000007|medical|missing', 'P000007|service_card|missing', 'P000007|passport|missing',
            'P000008|medical|missing', 'P000008|passport|missing', 'P000009|medical|missing',
            'P000009|service_card|missing', 'P000009|contract|missing', 'P000003|medical|missing',
            'P000004|medical|missing', 'P000003|service_card|missing', 'P000004|passport|missing',
            'P000010|service_card|expired', 'P000020|service_card|expired', 'P000023|contract|expired',
            'P000027|passport|expired', 'P000030|service_card|expired', 'P000003|contract|expired',
            'P000014|contract|expired',
        ], $rows->getCollection()->map(fn (array $row): string => $row['tabel_no'].'|'.$row['document_type'].'|'.$row['status'])->all());

        $missing = $rows->getCollection()->firstWhere('document_type', 'medical');
        $this->assertNull($missing['record_id']);
        $this->assertSame('Tibbi arayış', $missing['document_label']);
        $this->assertSame(__('compliance::documents.labels.not_available'), $missing['expires_at']);
        $this->assertNull($missing['days_left']);

        // A page past the end clamps to the last one instead of rendering empty.
        $this->assertSame(5, app(DocumentExpiryReadService::class)->dashboard([], 99)['rows']->currentPage());
    }

    public function test_filters_and_counts_match_the_previous_in_memory_results(): void
    {
        DocumentExpiryFixture::seedMixed();
        $service = app(DocumentExpiryReadService::class);
        $all = ['total' => 113, 'expired' => 11, 'expiring_30' => 13, 'expiring_60' => 10, 'valid' => 11, 'missing' => 68, 'critical' => 79, 'compliance_score' => 30];

        $cases = [
            [[], $all, ['service_card' => 29, 'passport' => 28, 'contract' => 28], 113],
            [['status' => 'expiring_30'], $all, ['service_card' => 5, 'passport' => 3, 'contract' => 5], 13],
            [
                ['type' => 'passport', 'search' => 'twin'],
                ['total' => 2, 'expired' => 0, 'expiring_30' => 0, 'expiring_60' => 1, 'valid' => 0, 'missing' => 1, 'critical' => 1, 'compliance_score' => 30],
                ['service_card' => 2, 'passport' => 2, 'contract' => 2],
                2,
            ],
            [
                ['status' => 'missing', 'type' => 'medical'],
                ['total' => 28, 'expired' => 0, 'expiring_30' => 0, 'expiring_60' => 0, 'valid' => 0, 'missing' => 28, 'critical' => 28, 'compliance_score' => 30],
                ['service_card' => 14, 'passport' => 18, 'contract' => 8],
                28,
            ],
            [
                ['search' => '2026-01-01 tarixindən'],
                ['total' => 20, 'expired' => 5, 'expiring_30' => 5, 'expiring_60' => 4, 'valid' => 6, 'missing' => 0, 'critical' => 5, 'compliance_score' => 30],
                ['service_card' => 0, 'passport' => 0, 'contract' => 20],
                20,
            ],
        ];

        foreach ($cases as [$filters, $summary, $typeCounts, $total]) {
            $payload = $service->dashboard($filters);

            $this->assertSame($summary, $payload['summary'], json_encode($filters));
            $this->assertSame($typeCounts, $payload['typeCounts'], json_encode($filters));
            $this->assertSame($total, $payload['rows']->total(), json_encode($filters));
            $this->assertSame(self::STRUCTURE_SCORES, $payload['structureScores']->all());
            $this->assertSame($total, $service->rows($filters)->count());
        }
    }

    public function test_sql_status_follows_the_days_left_thresholds(): void
    {
        DocumentExpiryFixture::seedMixed();

        $rows = app(DocumentExpiryReadService::class)->rows()->where('status', '!=', 'missing');

        // Every boundary of the fixture is present: -1, 0, 30, 31, 60, 61 days and no expiry.
        $this->assertEmpty(array_diff([-1, 0, 30, 31, 60, 61], $rows->pluck('days_left')->all()));
        $this->assertTrue($rows->contains(fn (array $row): bool => $row['days_left'] === null));

        foreach ($rows as $row) {
            $expected = match (true) {
                $row['days_left'] === null => 'valid',
                $row['days_left'] < 0 => 'expired',
                $row['days_left'] <= 30 => 'expiring_30',
                $row['days_left'] <= 60 => 'expiring_60',
                default => 'valid',
            };

            $this->assertSame($expected, $row['status'], $row['tabel_no'].' '.$row['document_type']);
        }
    }

    public function test_status_windows_come_from_each_types_requirement_row(): void
    {
        DocumentExpiryFixture::seedMixed();

        // Passport: critical 10 / warning 20. Service card: warning below critical (a warning
        // window that cannot hold anything). Contract: no requirement row → 30 / 60 fallback.
        DB::table('compliance_document_requirements')->where('key', 'passport')->update(['critical_days' => 10, 'warning_days' => 20]);
        DB::table('compliance_document_requirements')->where('key', 'service_card')->update(['critical_days' => 40, 'warning_days' => 20]);
        DB::table('compliance_document_requirements')->where('key', 'contract')->delete();
        DB::table('personnel_passports')->insert([
            'tabel_no' => 'P000001', 'serial_number' => 'AZE-15', 'given_date' => '2020-01-01',
            'valid_date' => today()->addDays(15)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(DocumentExpiryReadService::class);
        $windows = ['passport' => [10, 20], 'service_card' => [40, 40], 'contract' => [30, 60]];

        $this->assertSame(
            ['service_card' => ['critical' => 40, 'warning' => 40], 'passport' => ['critical' => 10, 'warning' => 20], 'contract' => ['critical' => 30, 'warning' => 60]],
            $service->dashboard()['typeWindows']
        );
        $this->assertSame('expiring_60', $service->rows(['search' => 'AZE-15'])->sole()['status']);

        $rows = $service->rows()->where('status', '!=', 'missing');
        foreach ($rows as $row) {
            [$critical, $warning] = $windows[$row['document_type']];
            $expected = match (true) {
                $row['days_left'] === null => 'valid',
                $row['days_left'] < 0 => 'expired',
                $row['days_left'] <= $critical => 'expiring_30',
                $row['days_left'] <= $warning => 'expiring_60',
                default => 'valid',
            };

            $this->assertSame($expected, $row['status'], $row['tabel_no'].' '.$row['document_type'].' '.$row['days_left']);
        }

        // Sanity: a 59-day service card is past its 40-day window (valid), not the default "approaching".
        $this->assertTrue($rows->contains(fn (array $row): bool => $row['document_type'] === 'service_card' && $row['days_left'] === 59 && $row['status'] === 'valid'));
    }

    public function test_query_count_does_not_grow_with_the_dataset(): void
    {
        DocumentExpiryFixture::seedMixed(10);
        $small = $this->dashboardQueries(['search' => 'name']);

        DocumentExpiryFixture::seedMixed(120, 'X');
        $large = $this->dashboardQueries(['search' => 'name']);

        $this->assertSame(count($small), count($large));
        $this->assertLessThanOrEqual(5, count($large));
        $this->assertTrue(collect($large)->contains(fn (string $sql): bool => str_contains($sql, 'limit 25')));
    }

    /**
     * @return list<string>
     */
    private function dashboardQueries(array $filters): array
    {
        $service = app(DocumentExpiryReadService::class);
        $service->dashboard($filters); // warms the memoized table listing
        DB::flushQueryLog();
        DB::enableQueryLog();

        $rows = $service->dashboard($filters, 2)['rows'];

        DB::disableQueryLog();
        $this->assertLessThanOrEqual(25, $rows->count());

        return collect(DB::getQueryLog())->pluck('query')->all();
    }
}
