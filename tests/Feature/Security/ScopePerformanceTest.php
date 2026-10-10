<?php

use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestSession;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\TalentAssessment;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\Feedback360Service;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceEvaluationReportingService;
use App\Modules\PerformanceEvaluation\Application\Services\SuccessionService;
use App\Modules\PerformanceEvaluation\Livewire\Feedback360Workspace;
use App\Modules\PerformanceEvaluation\Livewire\Lists;
use App\Modules\PerformanceEvaluation\Livewire\PersonnelPicker;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Performans modulu (360°, qiymətləndirmə formaları, testlər, 9-box) qiymətləndirilən işçinin
 * strukturu ilə məhdudlaşır: show-performance-evaluation icazəsi başqa strukturu açmır.
 */
beforeEach(function (): void {
    $this->own = Structure::query()->create(['name' => 'Öz şöbə', 'shortname' => 'OS']);
    $this->foreign = Structure::query()->create(['name' => 'Yad şöbə', 'shortname' => 'YS']);

    $this->ownPerson = scopePerformanceMakePersonnel('Görünənov', $this->own->id);
    $this->foreignPerson = scopePerformanceMakePersonnel('Gizliyev', $this->foreign->id);

    $this->cycle = PerformanceCycle::query()->create([
        'name' => '2026', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'status' => 'active',
    ]);
    $this->template = PerformanceFormTemplate::query()->create(['name' => 'Core', 'code' => 'CORE', 'is_active' => true]);

    $this->viewer = grantStructures(User::factory()->create(), [$this->own->id]);
    foreach (['show-performance-evaluation', 'manage-performance-evaluation', 'review-performance-evaluation', 'export-performance-evaluation'] as $permission) {
        $this->viewer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
});

function scopePerformanceMakePersonnel(string $surname, int $structureId): Personnel
{
    $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'TB'.Str::upper(Str::random(6)),
        'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
        'birthdate' => '1985-01-01', 'gender' => 1,
        'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
        'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
        'structure_id' => $structureId, 'position_id' => $position->id,
        'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
    ]));
}

it('shows and manages only 360 requests whose subject is in scope', function (): void {
    $service = app(Feedback360Service::class);
    $ownRequest = $service->createRequest($this->cycle->id, $this->template->id, $this->ownPerson->id, true, null);
    $foreignRequest = $service->createRequest($this->cycle->id, $this->template->id, $this->foreignPerson->id, true, null);

    expect($service->requests(null, $this->viewer)->pluck('id')->all())->toBe([$ownRequest->id])
        ->and($service->summary($this->viewer)['requests'])->toBe(1)
        ->and($service->find($foreignRequest->id, $this->viewer))->toBeNull();

    $this->actingAs($this->viewer);

    Livewire::test(Feedback360Workspace::class)
        ->assertSee('Görünənov')
        ->assertDontSee('Gizliyev')
        ->call('openDetail', $foreignRequest->id)
        ->assertNotFound();

    Livewire::test(Feedback360Workspace::class)
        ->call('deleteRequest', $foreignRequest->id)
        ->assertNotFound();

    expect(PerformanceFeedbackRequest::query()->whereKey($foreignRequest->id)->exists())->toBeTrue();

    Livewire::test(Feedback360Workspace::class)
        ->set('createForm.performance_cycle_id', $this->cycle->id)
        ->set('createForm.performance_form_template_id', $this->template->id)
        ->set('createForm.subject_personnel_id', $this->foreignPerson->id)
        ->call('saveRequest')
        ->assertForbidden();
});

it('scopes evaluation forms, test sessions and the personnel picker', function (): void {
    $ownForm = PerformanceForm::query()->create([
        'performance_cycle_id' => $this->cycle->id, 'performance_form_template_id' => $this->template->id, 'personnel_id' => $this->ownPerson->id,
    ]);
    $foreignForm = PerformanceForm::query()->create([
        'performance_cycle_id' => $this->cycle->id, 'performance_form_template_id' => $this->template->id, 'personnel_id' => $this->foreignPerson->id,
    ]);
    $bank = PerformanceTestBank::query()->create(['name' => 'Bank', 'code' => 'BNK', 'is_active' => true]);
    PerformanceTestSession::query()->create(['performance_test_bank_id' => $bank->id, 'personnel_id' => $this->foreignPerson->id, 'status' => 'assigned']);

    $this->actingAs($this->viewer);

    $rows = Livewire::test(Lists::class)->instance()->rows;
    expect($rows->getCollection()->pluck('id')->all())->toBe([$ownForm->id]);

    $sessions = Livewire::test(Lists::class)->set('entity', 'test_sessions')->instance()->rows;
    expect($sessions->total())->toBe(0);

    $reporting = app(PerformanceEvaluationReportingService::class);
    expect($reporting->formRows()->pluck('id')->all())->toBe([$ownForm->id])
        ->and($reporting->testSessionRows()->count())->toBe(0);

    $results = Livewire::test(PersonnelPicker::class, ['target' => 'subject'])->set('query', 'yev')->instance()->results;
    expect(collect($results)->pluck('id')->all())->not->toContain($this->foreignPerson->id);

    expect(PerformanceForm::query()->whereKey($foreignForm->id)->exists())->toBeTrue();
});

it('limits the nine-box to visible employees', function (): void {
    TalentAssessment::query()->create(['personnel_id' => $this->ownPerson->id, 'performance_cycle_id' => $this->cycle->id, 'performance_level' => 3, 'potential_level' => 3]);
    TalentAssessment::query()->create(['personnel_id' => $this->foreignPerson->id, 'performance_cycle_id' => $this->cycle->id, 'performance_level' => 3, 'potential_level' => 3]);

    $people = collect(app(SuccessionService::class)->nineBox($this->cycle->id, $this->viewer))
        ->pluck('people')->flatten(1)->pluck('personnel_id')->all();

    expect($people)->toBe([$this->ownPerson->id]);
});
