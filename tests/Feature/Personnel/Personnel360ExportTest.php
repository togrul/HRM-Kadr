<?php

namespace Tests\Feature\Personnel;

use App\Contracts\DocumentPdfConverter;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Personnel\Application\Services\Personnel360ExportService;
use App\Modules\Personnel\Exports\Personnel360TimelineExport;
use App\Modules\Personnel\Livewire\Employee360Timeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

/**
 * Əməkdaş 360 ixracı (PDF/XLSX): icazə, struktur görünürlüyü və həssas hadisələrin
 * (intizam, tibbi) icazə əsaslı redaksiyası.
 */
class Personnel360ExportTest extends TestCase
{
    use RefreshDatabase;

    private Personnel $personnel;

    private Structure $structure;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->structure = Structure::query()->create(['name' => 'Kadrlar', 'shortname' => 'KD']);
        $this->personnel = $this->makePersonnel($this->structure->id);
        $this->seedTimeline($this->personnel);
    }

    public function test_xlsx_export_redacts_sensitive_events_without_permission(): void
    {
        Excel::fake();
        $this->actingAs($this->viewer(['export-personnel-360']));

        $this->get(route('personnel.360.export', ['personnel' => $this->personnel->id, 'format' => 'xlsx']))->assertOk();

        Excel::assertDownloaded($this->filename('xlsx'), function (Personnel360TimelineExport $export): bool {
            $text = json_encode($export->array(), JSON_UNESCAPED_UNICODE);

            $this->assertStringContainsString(__('personnel::portfolio.timeline_titles.redacted_disciplinary'), (string) $text);
            $this->assertStringContainsString(__('personnel::portfolio.timeline_titles.redacted_medical'), (string) $text);
            $this->assertStringNotContainsString('Töhmət verilsin', (string) $text);
            $this->assertStringNotContainsString('Qrip', (string) $text);
            $this->assertStringContainsString('Adi əmr mətni', (string) $text);
            $this->assertSame(__('personnel::portfolio.export.columns.title'), $export->headings()[2]);

            return true;
        });
    }

    public function test_sensitive_and_medical_permissions_lift_the_redaction(): void
    {
        $service = app(Personnel360ExportService::class);

        $sensitive = collect($service->rows($this->personnel, $this->viewer(['export-personnel-360', 'view-personnel-360-sensitive'])));
        $this->assertTrue($sensitive->contains(fn (array $row): bool => str_contains($row['summary'], 'Töhmət verilsin')));
        $this->assertTrue($sensitive->contains(fn (array $row): bool => str_contains($row['summary'], 'Qrip')));
        $this->assertFalse($sensitive->contains('redacted', true));

        $medical = collect($service->rows($this->personnel, $this->viewer(['export-personnel-360', 'view-medical-diagnosis'])));
        $this->assertTrue($medical->contains(fn (array $row): bool => str_contains($row['summary'], 'Qrip')));
        $this->assertFalse($medical->contains(fn (array $row): bool => str_contains($row['summary'], 'Töhmət verilsin')));
        $this->assertSame(2, $medical->where('redacted', true)->count());
    }

    public function test_a_search_cannot_reveal_a_redacted_event(): void
    {
        $rows = app(Personnel360ExportService::class)->rows($this->personnel, $this->viewer(['export-personnel-360']), ['search' => 'töhmət']);

        $this->assertSame([], $rows);
    }

    public function test_pdf_export_converts_a_redacted_docx(): void
    {
        $converter = new class implements DocumentPdfConverter
        {
            public ?string $documentXml = null;

            public function isAvailable(): bool
            {
                return true;
            }

            public function convert(string $docxPath): ?string
            {
                $zip = new ZipArchive;
                $zip->open($docxPath);
                $this->documentXml = (string) $zip->getFromName('word/document.xml');
                $zip->close();

                $pdf = storage_path('app/tmp/'.Str::uuid().'.pdf');
                file_put_contents($pdf, '%PDF-1.4 test');

                return $pdf;
            }
        };
        $this->app->instance(DocumentPdfConverter::class, $converter);
        $this->actingAs($this->viewer(['export-personnel-360']));

        $response = $this->get(route('personnel.360.export', ['personnel' => $this->personnel->id, 'format' => 'pdf']));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString($this->filename('pdf'), (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString(e(__('personnel::portfolio.timeline_titles.redacted_disciplinary')), (string) $converter->documentXml);
        $this->assertStringNotContainsString('Töhmət verilsin', (string) $converter->documentXml);
        $this->assertStringContainsString('Adi əmr mətni', (string) $converter->documentXml);
    }

    public function test_pdf_export_answers_503_without_a_converter(): void
    {
        $this->app->instance(DocumentPdfConverter::class, new class implements DocumentPdfConverter
        {
            public function isAvailable(): bool
            {
                return false;
            }

            public function convert(string $docxPath): ?string
            {
                return null;
            }
        });
        $this->actingAs($this->viewer(['export-personnel-360']));

        $this->get(route('personnel.360.export', ['personnel' => $this->personnel->id, 'format' => 'pdf']))->assertStatus(503);
    }

    public function test_export_requires_the_export_permission(): void
    {
        $this->actingAs($this->viewer([]));

        $this->get(route('personnel.360.export', ['personnel' => $this->personnel->id, 'format' => 'xlsx']))->assertForbidden();
    }

    public function test_export_is_refused_outside_the_viewers_structures(): void
    {
        Excel::fake();
        $other = Structure::query()->create(['name' => 'Kənar', 'shortname' => 'KN']);
        $this->actingAs($this->viewer(['export-personnel-360'], [$other->id]));

        $this->get(route('personnel.360.export', ['personnel' => $this->personnel->id, 'format' => 'xlsx']))->assertForbidden();
    }

    public function test_the_timeline_panel_offers_export_only_with_permission_and_redacts_in_place(): void
    {
        $this->app->instance(DocumentPdfConverter::class, new class implements DocumentPdfConverter
        {
            public function isAvailable(): bool
            {
                return false;
            }

            public function convert(string $docxPath): ?string
            {
                return null;
            }
        });

        $this->actingAs($this->viewer([]));
        Livewire::test(Employee360Timeline::class, ['personnelId' => $this->personnel->id])
            ->assertDontSeeHtml('data-employee-360-export')
            ->assertSee(__('personnel::portfolio.timeline_titles.redacted_disciplinary'))
            ->assertDontSee('Töhmət verilsin');

        $this->actingAs($this->viewer(['export-personnel-360', 'view-personnel-360-sensitive']));
        Livewire::test(Employee360Timeline::class, ['personnelId' => $this->personnel->id])
            ->set('type', 'order')
            ->assertSeeHtml('data-employee-360-export')
            ->assertSeeHtml('/360/export/xlsx?type=order')
            ->assertDontSeeHtml('/360/export/pdf')
            ->assertSee('Töhmət verilsin');
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<int>|null  $structures
     */
    private function viewer(array $permissions, ?array $structures = null): User
    {
        $user = User::factory()->create();
        grantStructures($user, $structures ?? [$this->structure->id]);

        foreach (['show-personnels', ...$permissions] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['show-personnels', ...$permissions]);

        return $user->fresh();
    }

    private function filename(string $extension): string
    {
        return 'employee-360-'.Str::slug($this->personnel->tabel_no).'-'.now()->format('Ymd').'.'.$extension;
    }

    private function seedTimeline(Personnel $personnel): void
    {
        DB::table('order_word_templates')->insert([
            'code' => 'test_disciplinary', 'label' => 'İntizam', 'docx_path' => 'x.docx', 'effect' => 'disciplinary', 'variables' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $creator = User::factory()->create()->id;

        foreach ([
            ['ORD-1', 'Töhmət verilsin', ['template_code' => 'test_disciplinary']],
            ['ORD-2', 'Töhmət verilsin (təsdiqli)', ['template_code' => 'missing', 'applied_effect' => 'disciplinary']],
            ['ORD-3', 'Adi əmr mətni', ['template_code' => 'missing', 'applied_effect' => 'transfer']],
        ] as [$number, $description, $snapshot]) {
            DB::table('order_logs')->insert([
                'order_id' => 1, 'order_no' => $number, 'given_date' => '2026-05-01', 'given_by' => 'Rəhbər', 'given_by_rank' => '',
                'description' => $description, 'status_id' => 20, 'template_snapshot' => json_encode($snapshot), 'creator_id' => $creator,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('order_log_personnels')->insert(['order_no' => $number, 'tabel_no' => $personnel->tabel_no]);
        }

        $sickLeave = DB::table('leaves')->insertGetId([
            'tabel_no' => $personnel->tabel_no, 'starts_at' => '2026-04-01', 'ends_at' => '2026-04-05', 'reason' => 'Qrip', 'status_id' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('leave_sick_certificates')->insert([
            'leave_id' => $sickLeave, 'series' => 'A', 'number' => '1001', 'status' => 'closed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('leaves')->insert([
            'tabel_no' => $personnel->tabel_no, 'starts_at' => '2026-03-01', 'ends_at' => '2026-03-01', 'reason' => 'Ailə işi', 'status_id' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makePersonnel(int $structureId): Personnel
    {
        $position = Position::query()->firstOrCreate(['name' => 'mütəxəssis']);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'EX'.Str::upper(Str::random(6)),
            'surname' => 'Həsənov',
            'name' => 'Murad',
            'patronymic' => 'Elçin',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $position->id,
            'join_work_date' => '2018-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
