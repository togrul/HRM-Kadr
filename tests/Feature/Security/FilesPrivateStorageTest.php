<?php

namespace Tests\Feature\Security;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\TrainingDeliveryRecord;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Support\Uploads\PersonnelPhoto;
use App\Support\Uploads\SecureFileResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File as FileRule;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Həssas yükləmələr özəl diskdə saxlanır və yalnız sahib qeydin icazəsini yoxlayan
 * route-larla verilir; endirmə cavabları brauzerdə skript kimi icra oluna bilməz.
 */
class FilesPrivateStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_svg_and_html_are_never_served_inline_and_downloads_are_hardened(): void
    {
        $viewer = grantAllStructures(User::factory()->create());
        $viewer->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));
        $candidate = $this->makeCandidate();

        foreach (['svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'html' => '<script>alert(1)</script>'] as $ext => $body) {
            Storage::disk('local')->put("candidates/{$candidate->id}/legacy.{$ext}", $body);
            $document = CandidateDocument::query()->create([
                'candidate_id' => $candidate->id,
                'display_name' => 'Köhnə',
                'original_name' => "x.{$ext}",
                'file_path' => "candidates/{$candidate->id}/legacy.{$ext}",
                'disk' => 'local',
                'mime_type' => 'image/svg+xml',
                'extension' => $ext,
                'size_bytes' => strlen($body),
                'category' => 'other',
            ]);

            $response = $this->signIn($viewer)->get(route('candidates.documents.download', ['document' => $document, 'inline' => 1]));

            $response->assertOk();
            $this->assertStringStartsWith('attachment', (string) $response->headers->get('Content-Disposition'));
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('sandbox', (string) $response->headers->get('Content-Security-Policy'));
        }
    }

    public function test_upload_rules_reject_svg(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $rule = FileRule::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

        $this->assertTrue(Validator::make(['f' => $svg], ['f' => [$rule]])->fails());

        $source = (string) file_get_contents(app_path('Modules/Candidates/Livewire/ApplicationStageActionPanel.php'));
        $this->assertDoesNotMatchRegularExpression("/File::types\\([^)]*'svg'/", $source, 'Mərhələ sənədi yükləməsi SVG qəbul etməməlidir.');
    }

    public function test_display_names_are_sanitized_and_temp_files_live_outside_the_web_root(): void
    {
        $this->assertSame('passwd.docx', SecureFileResponse::safeName('../../etc/passwd.docx', '/tmp/x.docx'));
        $this->assertSame('Əliyev Elçin_mezuniyyet.docx', SecureFileResponse::safeName("Əliyev\0 Elçin_mezuniyyet", '/tmp/x.docx'));

        $path = SecureFileResponse::temporaryPath('docx');
        $this->assertStringStartsWith(storage_path('app/tmp'), $path);
        $this->assertStringNotContainsString(public_path(), $path);
    }

    public function test_leave_documents_are_gated_by_policy_or_ownership(): void
    {
        $owner = $this->makePersonnel();
        $other = $this->makePersonnel();
        $leave = $this->makeLeave($owner->tabel_no, 'leaves/med.pdf');
        Storage::disk('local')->put('leaves/med.pdf', '%PDF-1.4 tibbi arayış');

        $this->signIn(User::factory()->create())->get(route('leaves.document', $leave))->assertForbidden();

        $this->signIn($this->linkedUser($other))->get(route('leaves.document', $leave))->assertForbidden();

        $this->signIn($this->linkedUser($owner))->get(route('leaves.document', $leave))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $hr = grantAllStructures(User::factory()->create());
        $hr->givePermissionTo(Permission::findOrCreate('show-leaves', 'web'));
        $this->signIn($hr)->get(route('leaves.document', $leave))->assertOk();
    }

    public function test_legacy_public_leave_document_is_still_served_through_the_gate(): void
    {
        $owner = $this->makePersonnel();
        $leave = $this->makeLeave($owner->tabel_no, 'leaves/old.pdf');
        Storage::disk('public')->put('leaves/old.pdf', '%PDF-1.4 köhnə');

        $this->signIn($this->linkedUser($owner))->get(route('leaves.document', $leave))->assertOk();
    }

    public function test_personnel_photo_route_is_gated_and_cacheable(): void
    {
        $person = $this->makePersonnel(['photo' => 'personnel/face.jpg']);
        Storage::disk('local')->put('personnel/face.jpg', "\xFF\xD8\xFF\xE0fake-jpeg");
        $url = PersonnelPhoto::url($person->id, $person->photo);

        $this->assertStringNotContainsString('/storage/', (string) $url);

        $this->signIn(User::factory()->create())->get($url)->assertForbidden();

        $viewer = grantAllStructures(User::factory()->create());
        $viewer->givePermissionTo(Permission::findOrCreate('show-personnels', 'web'));

        $response = $this->signIn($viewer)->get($url)->assertOk();
        $this->assertStringStartsWith('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=604800', (string) $response->headers->get('Cache-Control'));

        $this->signIn($this->linkedUser($person))->get($url)->assertOk();
    }

    public function test_onboarding_template_file_requires_library_access_or_assignment(): void
    {
        Storage::disk('local')->put('onboarding-documents/rules.pdf', '%PDF-1.4 qaydalar');
        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'Daxili qaydalar',
            'document_type' => 'policy',
            'version' => '1.0',
            'file_path' => 'onboarding-documents/rules.pdf',
            'disk' => 'local',
        ]);

        $this->assertStringNotContainsString('/storage/', (string) $template->fileUrl());

        $this->signIn(User::factory()->create())->get($template->fileUrl())->assertForbidden();

        $assignee = $this->makePersonnel();
        OnboardingDocumentAssignment::query()->create([
            'template_id' => $template->id,
            'personnel_id' => $assignee->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        $this->signIn($this->linkedUser($assignee))->get($template->fileUrl())->assertOk();
    }

    public function test_training_certificate_requires_training_access_or_ownership(): void
    {
        $owner = $this->makePersonnel();
        $sessionId = DB::table('training_sessions')->insertGetId(['title' => 'Təhlükəsizlik', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        Storage::disk('local')->put('training-certificates/c.pdf', '%PDF-1.4 sertifikat');
        $record = TrainingDeliveryRecord::query()->create([
            'training_session_id' => $sessionId,
            'personnel_id' => $owner->id,
            'certificate_path' => 'training-certificates/c.pdf',
            'certificate_name' => 'sertifikat.pdf',
            'completed_at' => now(),
        ]);

        $this->assertStringNotContainsString('/storage/', (string) $record->certificateUrl());

        $this->signIn(User::factory()->create())->get($record->certificateUrl())->assertForbidden();
        $this->signIn($this->linkedUser($owner))->get($record->certificateUrl())->assertOk();
    }

    public function test_privatize_command_moves_public_files_and_is_idempotent(): void
    {
        $owner = $this->makePersonnel(['photo' => 'personnel/p.jpg']);
        $this->makeLeave($owner->tabel_no, 'leaves/a.pdf');
        $template = OnboardingDocumentTemplate::query()->create([
            'title' => 'T', 'document_type' => 'policy', 'version' => '1.0',
            'file_path' => 'onboarding-documents/t.pdf', 'disk' => 'public',
        ]);

        Storage::disk('public')->put('personnel/p.jpg', 'jpeg');
        Storage::disk('public')->put('leaves/a.pdf', 'pdf');
        Storage::disk('public')->put('onboarding-documents/t.pdf', 'pdf2');

        $this->artisan('files:privatize')->assertSuccessful();

        foreach (['personnel/p.jpg', 'leaves/a.pdf', 'onboarding-documents/t.pdf'] as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertSame('local', $template->fresh()->disk);

        // İkinci işə salınma heç nəyi pozmur.
        $this->artisan('files:privatize')->assertSuccessful();
        Storage::disk('local')->assertExists('leaves/a.pdf');
    }

    private function makeCandidate(): Candidate
    {
        $structure = Structure::query()->create(['name' => 'Namizəd bölmə', 'shortname' => 'NB']);

        return Candidate::query()->create([
            'surname' => 'Aliyev',
            'name' => 'Ali',
            'patronymic' => 'Test',
            'structure_id' => $structure->id,
            'height' => 180,
            'creator_id' => grantAllStructures(User::factory()->create())->id,
        ]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function makePersonnel(array $overrides = []): Personnel
    {
        $structure = Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR']);
        $position = Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

        return Personnel::withoutEvents(fn () => Personnel::query()->create(array_merge([
            'tabel_no' => 'FS'.Str::upper(Str::random(6)),
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
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ], $overrides)));
    }

    private function makeLeave(string $tabelNo, string $documentPath): Leave
    {
        OrderStatus::query()->firstOrCreate(['id' => OrderStatusEnum::PENDING->value], ['locale' => 'az', 'name' => 'Gözləyən']);
        $type = LeaveType::query()->create(['name' => 'Xəstəlik', 'max_days' => 0, 'requires_document' => true]);

        return Leave::withoutEvents(fn (): Leave => Leave::query()->create([
            'tabel_no' => $tabelNo,
            'leave_type_id' => $type->id,
            'starts_at' => '2026-11-02',
            'ends_at' => '2026-11-03',
            'duration_unit' => 'day',
            'reason' => 'Xəstəlik',
            'status_id' => OrderStatusEnum::PENDING->value,
            'document_path' => $documentPath,
        ]));
    }

    /**
     * AuthenticateSession sessiyadakı parol heşini yoxlayır; test daxilində istifadəçini dəyişəndə
     * sessiya təmizlənməlidir, yoxsa yeni istifadəçi çıxışa yönləndirilir.
     */
    private function signIn(User $user): static
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user);
    }

    private function linkedUser(Personnel $personnel): User
    {
        $user = User::factory()->create();
        UserPersonnelLink::query()->create([
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'manual',
            'resolved_at' => now(),
        ]);

        return $user;
    }
}
