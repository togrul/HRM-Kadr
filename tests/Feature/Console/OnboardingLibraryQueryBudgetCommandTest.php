<?php

namespace Tests\Feature\Console;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentReceipt;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingLibraryQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('onboarding-library:query-budget', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('onboarding_library_dataset_empty', data_get($payload, 'summary.reason'));
    }

    public function test_it_stays_within_default_query_budgets_for_small_dataset(): void
    {
        $this->seedReferenceData();
        $personnel = $this->makePersonnel('employee@example.test');

        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'Uyğunlaşma sənədi',
            'document_type' => 'policy',
            'version' => '1.0',
            'file_path' => 'docs/onboarding.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'is_required' => true,
            'requires_acknowledgement' => true,
            'is_active' => true,
            'auto_assign_new_hires' => false,
            'created_by' => 1,
        ]);

        $assignment = OnboardingDocumentAssignment::query()->create([
            'template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => 1,
            'assigned_at' => now(),
            'status' => 'acknowledged',
        ]);

        OnboardingDocumentReceipt::query()->create([
            'assignment_id' => $assignment->id,
            'opened_at' => now(),
            'acknowledged_at' => now(),
        ]);

        $exitCode = Artisan::call('onboarding-library:query-budget', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertCount(3, data_get($payload, 'results', []));
    }

    private function makePersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
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
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }
        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert(['id' => 1, 'country_id' => 1, 'locale' => 'az', 'title' => 'Azərbaycan']);
        }
        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        }
        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        }
        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer']);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
