<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Livewire\CandidateFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CandidateFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_upload_candidate_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-candidates', 'web'));

        $candidate = $this->makeCandidate();

        $this->actingAs($user);

        Livewire::test(CandidateFiles::class, ['candidateModel' => $candidate->id])
            ->set('uploadedFile', UploadedFile::fake()->create('candidate-cv.pdf', 120))
            ->set('draft.display_name', 'Əsas CV')
            ->set('draft.category', 'cv')
            ->set('draft.notes', 'Müsahibə üçün təqdim olunan versiya')
            ->call('addFile')
            ->call('store')
            ->assertDispatched('candidateAdded')
            ->assertDispatched('closeSideMenu');

        $document = CandidateDocument::query()->firstOrFail();

        $this->assertSame($candidate->id, $document->candidate_id);
        $this->assertSame('Əsas CV', $document->display_name);
        $this->assertSame('cv', $document->category);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_unauthorized_user_cannot_open_candidate_files_modal(): void
    {
        $user = User::factory()->create();
        $candidate = $this->makeCandidate();

        $this->actingAs($user);

        Livewire::test(CandidateFiles::class, ['candidateModel' => $candidate->id])
            ->assertForbidden();
    }

    public function test_authorized_user_can_download_candidate_document(): void
    {
        Storage::fake('local');

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        $candidate = $this->makeCandidate();

        Storage::disk('local')->put('candidates/'.$candidate->id.'/documents/test.pdf', 'document-body');

        $document = CandidateDocument::query()->create([
            'candidate_id' => $candidate->id,
            'display_name' => 'CV',
            'original_name' => 'cv.pdf',
            'file_path' => 'candidates/'.$candidate->id.'/documents/test.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 12,
            'category' => 'cv',
        ]);

        $this->actingAs($viewer)
            ->get(route('candidates.documents.download', $document))
            ->assertOk();
    }

    public function test_authorized_user_can_preview_candidate_image_inline(): void
    {
        Storage::fake('local');

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        $candidate = $this->makeCandidate();

        Storage::disk('local')->put('candidates/'.$candidate->id.'/documents/test.png', 'image-body');

        $document = CandidateDocument::query()->create([
            'candidate_id' => $candidate->id,
            'display_name' => 'Preview',
            'original_name' => 'preview.png',
            'file_path' => 'candidates/'.$candidate->id.'/documents/test.png',
            'disk' => 'local',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 12,
            'category' => 'other',
        ]);

        $this->actingAs($viewer)
            ->get(route('candidates.documents.download', ['document' => $document, 'inline' => 1]))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=preview.png');
    }

    public function test_existing_document_metadata_can_be_updated(): void
    {
        Storage::fake('local');

        $editor = User::factory()->create();
        $editor->givePermissionTo(Permission::findOrCreate('edit-candidates', 'web'));

        $candidate = $this->makeCandidate();

        $document = CandidateDocument::query()->create([
            'candidate_id' => $candidate->id,
            'display_name' => 'Old CV',
            'original_name' => 'cv.pdf',
            'file_path' => 'candidates/'.$candidate->id.'/documents/test.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 12,
            'category' => 'cv',
            'notes' => 'Old note',
        ]);

        $this->actingAs($editor);

        Livewire::test(CandidateFiles::class, ['candidateModel' => $candidate->id])
            ->set('files.0.display_name', 'Updated CV')
            ->set('files.0.category', 'test_result')
            ->set('files.0.notes', 'Updated note')
            ->call('store')
            ->assertDispatched('candidateAdded');

        $document->refresh();

        $this->assertSame('Updated CV', $document->display_name);
        $this->assertSame('test_result', $document->category);
        $this->assertSame('Updated note', $document->notes);
    }

    public function test_files_can_be_filtered_by_category(): void
    {
        $editor = User::factory()->create();
        $editor->givePermissionTo(Permission::findOrCreate('edit-candidates', 'web'));

        $candidate = $this->makeCandidate();

        CandidateDocument::query()->create([
            'candidate_id' => $candidate->id,
            'display_name' => 'Medical Report',
            'original_name' => 'medical.pdf',
            'file_path' => 'candidates/'.$candidate->id.'/documents/medical.pdf',
            'disk' => 'local',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'category' => 'medical',
        ]);

        CandidateDocument::query()->create([
            'candidate_id' => $candidate->id,
            'display_name' => 'Candidate CV',
            'original_name' => 'cv.pdf',
            'file_path' => 'candidates/'.$candidate->id.'/documents/cv.pdf',
            'disk' => 'local',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'category' => 'cv',
        ]);

        $this->actingAs($editor);

        $component = Livewire::test(CandidateFiles::class, ['candidateModel' => $candidate->id])
            ->set('ui.category_filter', 'medical');

        $visibleNames = collect($component->instance()->visibleFiles)
            ->pluck('display_name')
            ->values()
            ->all();

        $this->assertSame(['Medical Report'], $visibleNames);
    }

    public function test_category_filter_falls_back_to_all_when_placeholder_clears_value(): void
    {
        $editor = User::factory()->create();
        $editor->givePermissionTo(Permission::findOrCreate('edit-candidates', 'web'));

        $candidate = $this->makeCandidate();

        $this->actingAs($editor);

        Livewire::test(CandidateFiles::class, ['candidateModel' => $candidate->id])
            ->set('ui.category_filter', null)
            ->assertSet('ui.category_filter', null);
    }

    private function makeCandidate(): Candidate
    {
        $structure = Structure::query()->create([
            'name' => 'Candidate Structure',
            'shortname' => 'CS',
        ]);

        $creator = User::factory()->create();

        return Candidate::query()->create([
            'surname' => 'Aliyev',
            'name' => 'Ali',
            'patronymic' => 'Test',
            'structure_id' => $structure->id,
            'height' => 180,
            'creator_id' => $creator->id,
        ]);
    }
}
