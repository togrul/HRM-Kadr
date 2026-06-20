<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_show_personnels_cannot_download_a_file(): void
    {
        $document = $this->makeDocumentWithFile();

        $this->actingAs(User::factory()->create());

        $this->get(route('personnel.files.download', $document->id))
            ->assertForbidden();
    }

    public function test_authorized_user_can_download_a_private_file(): void
    {
        $document = $this->makeDocumentWithFile();

        $this->actingAs($this->userWithPermission('show-personnels'));

        $response = $this->get(route('personnel.files.download', $document->id));

        $response->assertOk();
        $response->assertDownload('Diploma.pdf');
    }

    public function test_missing_physical_file_returns_404(): void
    {
        Storage::fake('local');
        $personnel = $this->makePersonnel();
        $document = PersonnelDocument::create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/does-not-exist.pdf',
            'filename' => 'Ghost',
        ]);

        $this->actingAs($this->userWithPermission('show-personnels'));

        $this->get(route('personnel.files.download', $document->id))
            ->assertNotFound();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $document = $this->makeDocumentWithFile();

        $this->get(route('personnel.files.download', $document->id))
            ->assertRedirect(route('login'));
    }

    private function makeDocumentWithFile(): PersonnelDocument
    {
        Storage::fake('local');
        $personnel = $this->makePersonnel();

        Storage::disk('local')->put('files/secret.pdf', '%PDF-1.4 confidential');

        return PersonnelDocument::create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/secret.pdf',
            'filename' => 'Diploma',
        ]);
    }

    private function makePersonnel(): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
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

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user;
    }
}
