<?php

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\CandidateDocument;
use App\Models\JobOpening;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Livewire\CandidateList;
use App\Modules\Candidates\Livewire\OpeningDetail;
use App\Modules\Candidates\Support\CandidateStructureScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Namizəd modulu struktur görünürlüyünə tabedir: başqa strukturun namizədi, müraciəti,
 * sənədi və vakansiyası açılmır; struktur verilməmiş istifadəçi heç nə görmür.
 */
beforeEach(function (): void {
    $this->own = Structure::query()->create(['name' => 'Öz şöbə', 'shortname' => 'OS']);
    $this->foreign = Structure::query()->create(['name' => 'Yad şöbə', 'shortname' => 'YS']);

    $this->creator = grantAllStructures(User::factory()->create());

    $this->ownCandidate = scopeCandidatesMakeCandidate('Görünən', $this->own->id, $this->creator);
    $this->foreignCandidate = scopeCandidatesMakeCandidate('Gizli', $this->foreign->id, $this->creator);

    $this->viewer = grantStructures(User::factory()->create(), [$this->own->id]);
    foreach (['show-candidates', 'edit-candidates', 'add-candidates'] as $permission) {
        $this->viewer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
});

function scopeCandidatesMakeCandidate(string $surname, ?int $structureId, User $creator): Candidate
{
    return Candidate::query()->create([
        'surname' => $surname,
        'name' => 'Namizəd',
        'patronymic' => 'Ata',
        'structure_id' => $structureId,
        'height' => 180,
        'creator_id' => $creator->id,
    ]);
}

function scopeCandidatesMakeOpening(int $structureId, User $owner): JobOpening
{
    $position = Position::query()->create(['name' => 'Vəzifə '.$structureId]);

    return JobOpening::query()->create([
        'title' => 'Vakansiya '.$structureId,
        'structure_id' => $structureId,
        'position_id' => $position->id,
        'profile_pack' => 'private',
        'headcount' => 1,
        'status' => 'open',
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
    ]);
}

it('denies candidate policy abilities outside the structure scope', function (): void {
    expect(Gate::forUser($this->viewer)->allows('view', $this->ownCandidate))->toBeTrue()
        ->and(Gate::forUser($this->viewer)->allows('view', $this->foreignCandidate))->toBeFalse()
        ->and(Gate::forUser($this->viewer)->allows('update', $this->foreignCandidate))->toBeFalse();

    $admin = grantAllStructures(User::factory()->create());
    $admin->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

    expect(Gate::forUser($admin)->allows('view', $this->foreignCandidate))->toBeTrue();
});

it('fails closed for a user whose roles grant no structure', function (): void {
    $nobody = User::factory()->create();
    $nobody->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

    expect(Gate::forUser($nobody)->allows('view', $this->ownCandidate))->toBeFalse();
});

it('scopes application policy by candidate and opening structure', function (): void {
    $ownOpening = scopeCandidatesMakeOpening($this->own->id, $this->creator);
    $foreignOpening = scopeCandidatesMakeOpening($this->foreign->id, $this->creator);

    $visible = CandidateApplication::query()->create([
        'candidate_id' => $this->ownCandidate->id, 'job_opening_id' => $ownOpening->id,
        'current_stage' => 'screening', 'status' => 'active',
    ]);
    $foreignOpeningApp = CandidateApplication::query()->create([
        'candidate_id' => $this->ownCandidate->id, 'job_opening_id' => $foreignOpening->id,
        'current_stage' => 'screening', 'status' => 'active',
    ]);
    $foreignCandidateApp = CandidateApplication::query()->create([
        'candidate_id' => $this->foreignCandidate->id, 'job_opening_id' => $ownOpening->id,
        'current_stage' => 'screening', 'status' => 'active',
    ]);

    $gate = Gate::forUser($this->viewer);

    expect($gate->allows('view', $visible))->toBeTrue()
        ->and($gate->allows('view', $foreignOpeningApp))->toBeFalse()
        ->and($gate->allows('view', $foreignCandidateApp))->toBeFalse()
        ->and($gate->allows('transition', $foreignCandidateApp))->toBeFalse();

    $this->actingAs($this->viewer);

    // Pipeline, vakansiya detalı və müraciət siyahıları eyni sorğu köməkçisindən keçir.
    $ids = CandidateStructureScope::constrainApplications(CandidateApplication::query(), $this->viewer)->pluck('id')->all();

    expect($ids)->toContain($visible->id)
        ->not->toContain($foreignOpeningApp->id)
        ->not->toContain($foreignCandidateApp->id);

    $this->get(route('candidates.applications.show', $foreignCandidateApp))->assertForbidden();
    $this->get(route('candidates.openings.show', $foreignOpening))->assertForbidden();

    $opening = Livewire::test(OpeningDetail::class, ['opening' => $ownOpening])->instance()->opening;
    expect($opening->applications->pluck('id')->all())->toBe([$visible->id]);
});

it('lists only candidates inside the structure scope', function (): void {
    $this->actingAs($this->viewer);

    Livewire::test(CandidateList::class)
        ->assertSee('Görünən')
        ->assertDontSee('Gizli');
});

it('refuses to download a document of an out-of-scope candidate', function (): void {
    Storage::fake('local');

    $makeDocument = function (Candidate $candidate): CandidateDocument {
        $path = 'candidates/'.$candidate->id.'/documents/cv.pdf';
        Storage::disk('local')->put($path, 'body');

        return CandidateDocument::query()->create([
            'candidate_id' => $candidate->id, 'display_name' => 'CV', 'original_name' => 'cv.pdf',
            'file_path' => $path, 'disk' => 'local', 'mime_type' => 'application/pdf',
            'extension' => 'pdf', 'size_bytes' => 4, 'category' => 'cv',
        ]);
    };

    $this->actingAs($this->viewer)
        ->get(route('candidates.documents.download', $makeDocument($this->ownCandidate)))
        ->assertOk();

    $this->actingAs($this->viewer)
        ->get(route('candidates.documents.download', $makeDocument($this->foreignCandidate)))
        ->assertForbidden();
});
