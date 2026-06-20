<?php

namespace Tests\Feature\Console;

use App\Models\Personnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComplianceDocumentQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_compliance_document_query_budget_passes_with_personnel_dataset(): void
    {
        $this->makePersonnel();

        $exitCode = Artisan::call('compliance:document-query-budget', [
            '--json' => true,
            '--dashboard-budget' => 35,
            '--rows-budget' => 20,
            '--reminders-budget' => 20,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertCount(3, data_get($payload, 'results'));
    }

    private function makePersonnel(): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'CQB001',
            'surname' => 'Compliance',
            'name' => 'Budget',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'compliance-budget@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'CQB0001',
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor']);
        DB::table('structures')->insertOrIgnore(['id' => 1, 'name' => 'Compliance HQ']);
        DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Compliance Officer']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time']);
    }
}
