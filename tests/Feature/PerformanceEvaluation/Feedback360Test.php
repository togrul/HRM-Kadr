<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\Feedback360Service;
use App\Modules\PerformanceEvaluation\Livewire\Feedback360Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Feedback360Test extends TestCase
{
    use RefreshDatabase;

    public function test_aggregate_averages_scores_per_item_and_by_rater_type(): void
    {
        [$cycle, $template, $items] = $this->templateWithTwoItems();
        $subject = $this->makePersonnel('Subyekt');
        $service = app(Feedback360Service::class);

        $request = $service->createRequest($cycle->id, $template->id, $subject->id, true, null);

        $manager = $service->addRater($request->id, 'manager', $this->makePersonnel('Menecer')->id);
        $peer1 = $service->addRater($request->id, 'peer', $this->makePersonnel('Həmkar1')->id);
        $peer2 = $service->addRater($request->id, 'peer', $this->makePersonnel('Həmkar2')->id);
        $self = $service->addRater($request->id, 'self', $this->makePersonnel('Ozu')->id);

        // item1 scores: 80,60,70,90 → avg 75 ; item2: 100,80,60,100 → avg 85
        $service->submitScores($manager->id, [$items[0] => 80, $items[1] => 100]);
        $service->submitScores($peer1->id, [$items[0] => 60, $items[1] => 80]);
        $service->submitScores($peer2->id, [$items[0] => 70, $items[1] => 60]);
        $service->submitScores($self->id, [$items[0] => 90, $items[1] => 100]);

        $aggregate = $service->aggregate($request->id);

        $this->assertSame(75.0, (float) $aggregate['items'][0]['average']);
        $this->assertSame(85.0, (float) $aggregate['items'][1]['average']);
        // by-type for item1: manager 80, peer (60+70)/2=65, self 90
        $this->assertSame(80.0, (float) $aggregate['items'][0]['by_type']['manager']);
        $this->assertSame(65.0, (float) $aggregate['items'][0]['by_type']['peer']);
        $this->assertSame(90.0, (float) $aggregate['items'][0]['by_type']['self']);
        $this->assertNull($aggregate['items'][0]['by_type']['subordinate']);
        // weighted raw final (50/50): (75+85)/2 = 80
        $this->assertSame(80.0, (float) $aggregate['raw_final']);
    }

    public function test_pending_raters_are_excluded_from_aggregate(): void
    {
        [$cycle, $template, $items] = $this->templateWithTwoItems();
        $subject = $this->makePersonnel('Subyekt');
        $service = app(Feedback360Service::class);
        $request = $service->createRequest($cycle->id, $template->id, $subject->id, true, null);

        $manager = $service->addRater($request->id, 'manager', $this->makePersonnel('Menecer')->id);
        $service->addRater($request->id, 'peer', $this->makePersonnel('Həmkar')->id); // never submits

        $service->submitScores($manager->id, [$items[0] => 50, $items[1] => 50]);

        $aggregate = $service->aggregate($request->id);
        $this->assertSame(50.0, (float) $aggregate['items'][0]['average']);
        $this->assertSame(1, $aggregate['items'][0]['count']);
    }

    public function test_calibration_computes_weighted_final_and_closes_request(): void
    {
        [$cycle, $template, $items] = $this->templateWithTwoItems();
        $subject = $this->makePersonnel('Subyekt');
        $service = app(Feedback360Service::class);
        $request = $service->createRequest($cycle->id, $template->id, $subject->id, true, null);

        $service->calibrate($request->id, [$items[0] => 70, $items[1] => 90], 'Razılaşdırıldı', true, 1);

        $request->refresh();
        $this->assertSame('80.00', (string) $request->final_score); // (70+90)/2 weighted 50/50
        $this->assertSame('approved', $request->calibration_status);
        $this->assertSame('closed', $request->status);
        $this->assertSame(70.0, (float) $request->calibrated_scores[$items[0]]);
    }

    public function test_workspace_creates_request_adds_rater_and_calibrates(): void
    {
        [$cycle, $template, $items] = $this->templateWithTwoItems();
        $subject = $this->makePersonnel('Subyekt');
        $rater = $this->makePersonnel('Menecer');
        $this->actingAs($this->userWith(['show-performance-evaluation', 'manage-performance-evaluation']));

        $component = Livewire::test(Feedback360Workspace::class)
            ->assertSet('cycleId', $cycle->id)
            ->call('openCreate')
            ->set('createForm.performance_cycle_id', $cycle->id)
            ->set('createForm.performance_form_template_id', $template->id)
            ->set('createForm.subject_personnel_id', $subject->id)
            ->call('saveRequest')
            ->assertHasNoErrors()
            ->assertSet('section', 'detail');

        $request = PerformanceFeedbackRequest::firstOrFail();
        $this->assertSame($subject->id, (int) $request->subject_personnel_id);

        // add a rater, capture scores
        $component
            ->set('raterForm.rater_type', 'manager')
            ->set('raterForm.rater_personnel_id', $rater->id)
            ->call('addRater')
            ->assertHasNoErrors();

        $raterModel = $request->raters()->firstOrFail();

        $component
            ->call('openScoring', $raterModel->id)
            ->set('scoreInputs.'.$items[0], 80)
            ->set('scoreInputs.'.$items[1], 60)
            ->call('saveScores')
            ->assertHasNoErrors();

        $raterModel->refresh();
        $this->assertSame('submitted', $raterModel->status);

        // calibrate + approve
        $component
            ->call('openCalibrate', $request->id)
            ->assertSet('section', 'calibrate')
            ->set('calibrationInputs.'.$items[0], 75)
            ->set('calibrationInputs.'.$items[1], 65)
            ->call('saveCalibration', true)
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame('closed', $request->status);
        $this->assertSame('70.00', (string) $request->final_score); // (75+65)/2
    }

    public function test_viewing_requires_permission(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Feedback360Workspace::class)->assertForbidden();
    }

    /**
     * @return array{0:PerformanceCycle,1:PerformanceFormTemplate,2:array<int,int>}
     */
    private function templateWithTwoItems(): array
    {
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 illik', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'status' => 'active',
        ]);
        $template = PerformanceFormTemplate::query()->create(['name' => 'Core', 'code' => 'CORE', 'is_active' => true]);
        $section = PerformanceFormTemplateSection::query()->create([
            'performance_form_template_id' => $template->id, 'name' => 'Bölmə', 'weight_percent' => 100, 'sort_order' => 1,
        ]);
        $item1 = PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id, 'name' => 'Meyar 1', 'weight_percent' => 50, 'sort_order' => 1,
        ]);
        $item2 = PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id, 'name' => 'Meyar 2', 'weight_percent' => 50, 'sort_order' => 2,
        ]);

        return [$cycle, $template, [$item1->id, $item2->id]];
    }

    private function makePersonnel(string $surname): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Şöbə '.Str::random(4), 'shortname' => 'S'.Str::upper(Str::random(3))]);
        $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structure->id, 'position_id' => $position->id,
            'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
