<?php

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingNeedItem;
use App\Models\User;
use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use App\Modules\Compliance\Livewire\DocumentExpiryDashboard;
use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use App\Modules\EmployeeLifecycle\Application\Services\LifecyclePlanTemplateService;
use App\Modules\EmployeeLifecycle\Livewire\Dashboard as LifecycleDashboard;
use App\Modules\TrainingNeeds\Livewire\Lists as TrainingLists;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Sənəd uyğunluğu, işçi həyat dövrü və təlim ehtiyacları ekranları işçi sətirlərini
 * istifadəçinin struktur görünürlüyü ilə məhdudlaşdırır (fail closed).
 */
beforeEach(function (): void {
    $this->own = Structure::query()->create(['name' => 'Öz şöbə', 'shortname' => 'OS']);
    $this->foreign = Structure::query()->create(['name' => 'Yad şöbə', 'shortname' => 'YS']);

    $this->ownPerson = scopeStrategicMakePersonnel('Görünənov', $this->own->id);
    $this->foreignPerson = scopeStrategicMakePersonnel('Gizliyev', $this->foreign->id);

    $this->viewer = grantStructures(User::factory()->create(), [$this->own->id]);
});

function scopeStrategicMakePersonnel(string $surname, int $structureId): Personnel
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

function scopeStrategicGrant(User $user, array $permissions): void
{
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
}

it('limits document compliance rows and export to visible employees', function (): void {
    scopeStrategicGrant($this->viewer, ['show-document-compliance']);

    $names = app(DocumentExpiryReadService::class)->forViewer($this->viewer)->rows()->pluck('personnel_name')->unique()->values();

    expect($names->implode(' '))->toContain('Görünənov')->not->toContain('Gizliyev');

    $nobody = User::factory()->create();
    expect(app(DocumentExpiryReadService::class)->forViewer($nobody)->rows())->toHaveCount(0);

    Livewire::actingAs($this->viewer)
        ->test(DocumentExpiryDashboard::class)
        ->assertSee('Görünənov')
        ->assertDontSee('Gizliyev');
});

it('limits lifecycle queues and refuses actions on out-of-scope records', function (): void {
    scopeStrategicGrant($this->viewer, ['show-employee-lifecycle', 'manage-employee-lifecycle']);

    $plans = app(LifecyclePlanTemplateService::class);
    $ownReview = $plans->scheduleProbationReview($this->ownPerson->id, now()->addWeek());
    $foreignReview = $plans->scheduleProbationReview($this->foreignPerson->id, now()->addWeek());

    $service = app(LifecycleDashboardReadService::class)->forViewer($this->viewer);

    expect($service->probationReviews()->pluck('id')->all())->toBe([$ownReview])
        ->and($service->isRecordVisible('employee_lifecycle_probation_reviews', $foreignReview))->toBeFalse()
        ->and($service->dashboard()['events']->getCollection()->pluck('employee_name')->implode(' '))->not->toContain('Gizliyev');

    Livewire::actingAs($this->viewer)
        ->test(LifecycleDashboard::class)
        ->set('completionForm.probation_review_id', $foreignReview)
        ->set('completionForm.probation_decision', 'confirm')
        ->call('completeProbationReview')
        ->assertNotFound();

    expect(DB::table('employee_lifecycle_probation_reviews')->where('id', $foreignReview)->value('status'))->toBe('pending');

    Livewire::actingAs($this->viewer)
        ->test(LifecycleDashboard::class)
        ->set('probationForm.personnel_id', $this->foreignPerson->id)
        ->set('probationForm.review_due_at', now()->addWeek()->toDateString())
        ->call('scheduleProbation')
        ->assertForbidden();
});

it('limits training need lists to visible employees', function (): void {
    scopeStrategicGrant($this->viewer, ['show-training-needs']);

    $group = TrainingCompetencyGroup::query()->create(['name' => 'Qrup', 'slug' => 'qrup', 'is_active' => true]);
    $competency = TrainingCompetency::query()->create([
        'training_competency_group_id' => $group->id, 'name' => 'Bacarıq', 'slug' => 'bacariq', 'is_active' => true,
    ]);

    $own = TrainingNeedItem::query()->create(['personnel_id' => $this->ownPerson->id, 'training_competency_id' => $competency->id]);
    TrainingNeedItem::query()->create(['personnel_id' => $this->foreignPerson->id, 'training_competency_id' => $competency->id]);

    $rows = Livewire::actingAs($this->viewer)->test(TrainingLists::class)->instance()->rows;

    expect($rows->getCollection()->pluck('id')->all())->toBe([$own->id]);
});
