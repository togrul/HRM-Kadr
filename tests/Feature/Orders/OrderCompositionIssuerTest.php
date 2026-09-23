<?php

namespace Tests\Feature\Orders;

use App\Models\Candidate;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderDocumentBuilder;
use App\Modules\Orders\Infrastructure\Document\OrderSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

/**
 * The composer's services without the Livewire layer: the issuer's outcomes, the
 * subject rules and the document builder's system context.
 */
class OrderCompositionIssuerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_a_blank_order_number_is_rejected_on_its_input(): void
    {
        $outcome = $this->issuer()->issue($this->leaveTemplate(), $this->composition(orderNumber: '  '), false);

        $this->assertFalse($outcome->isSaved());
        $this->assertSame(['orderNumber'], array_keys($outcome->errors));
        $this->assertNull($outcome->message);
    }

    public function test_missing_manual_fields_are_rejected_per_input_with_a_summary(): void
    {
        $personnel = $this->makePersonnel();

        $outcome = $this->issuer()->issue($this->leaveTemplate(), $this->composition(personnelId: $personnel->id), false);

        $this->assertFalse($outcome->isSaved());
        $this->assertSame(['fields.var_2'], array_keys($outcome->errors));
        $this->assertStringContainsString('Başlama tarixi', (string) $outcome->message);
        $this->assertSame(0, OrderLog::count());
    }

    public function test_a_saved_order_carries_its_stored_document(): void
    {
        $personnel = $this->makePersonnel();

        $outcome = $this->issuer()->issue(
            $this->leaveTemplate(),
            $this->composition(personnelId: $personnel->id, fields: ['var_2' => '19.05.2026']),
            false,
        );

        $this->assertTrue($outcome->isSaved());
        $this->assertSame(__('orders::order_composer.messages.order_issued'), $outcome->message);
        Storage::disk('local')->assertExists((string) $outcome->documentPath);
        $this->assertSame($outcome->documentPath, data_get(OrderLog::sole()->template_snapshot, 'docx_path'));
    }

    public function test_a_hire_without_a_free_slot_asks_to_create_one(): void
    {
        $structure = Structure::query()->create(['name' => 'Mərkəzi anbar', 'shortname' => 'MA']);
        $position = Position::query()->create(['name' => 'sürücü']);
        $candidate = $this->makeCandidate($structure);

        $outcome = $this->issuer()->issue($this->hireTemplate(), $this->composition(
            presetCode: 'hire',
            candidateId: $candidate->id,
            hireStructureId: $structure->id,
            hirePositionId: $position->id,
            fields: ['var_2' => '09.06.2026'],
        ), false);

        $this->assertTrue($outcome->isVacancyMissing());
        $this->assertStringContainsString('Mərkəzi anbar', (string) $outcome->message);
        $this->assertStringContainsString('sürücü', (string) $outcome->message);
        $this->assertSame(0, OrderLog::count());
    }

    public function test_hire_subject_needs_a_candidate_then_a_target(): void
    {
        $subjects = app(OrderSubjectResolver::class);
        $template = $this->hireTemplate();

        $this->assertSame(['candidateId'], array_keys($subjects->subjectErrors($template, $this->composition())));
        $this->assertSame(['hirePositionId'], array_keys($subjects->subjectErrors($template, $this->composition(candidateId: 1, hireStructureId: 1))));
        $this->assertSame([], $subjects->subjectErrors($template, $this->composition(candidateId: 1, hireStructureId: 1, hirePositionId: 1)));
    }

    public function test_the_hire_subject_is_a_transient_employee_in_the_target_post(): void
    {
        $structure = Structure::query()->create(['name' => 'Mərkəzi anbar', 'shortname' => 'MA']);
        $position = Position::query()->create(['name' => 'sürücü']);
        $candidate = $this->makeCandidate($structure);

        $subject = app(OrderSubjectResolver::class)->subject($this->hireTemplate(), $this->composition(
            candidateId: $candidate->id,
            hireStructureId: $structure->id,
            hirePositionId: $position->id,
        ));

        $this->assertNotNull($subject);
        $this->assertFalse($subject->exists);
        $this->assertSame('Hüseynov', $subject->surname);
        $this->assertSame('sürücü', $subject->position?->name);
        $this->assertSame('Mərkəzi anbar', $subject->structure?->name);
    }

    public function test_the_system_context_carries_the_order_and_its_signatory(): void
    {
        $context = app(OrderDocumentBuilder::class)->systemContext(
            $this->composition(orderNumber: '5-M', orderDate: '1 iyun 2026'),
            ['fullname' => 'Sührabov Sübhan', 'title' => 'rəis'],
        );

        $this->assertSame([
            'system.order_number' => '5-M',
            'system.order_date' => '1 iyun 2026',
            'system.organization_city' => 'Bakı şəhəri',
            'system.signatory_full_name' => 'Sührabov Sübhan',
            'system.signatory_title' => 'rəis',
        ], $context);
    }

    public function test_the_download_name_is_filesystem_safe(): void
    {
        $this->assertSame('leave_2026-ƏM-145.docx', $this->composition(orderNumber: '2026/ƏM-145')->downloadName());
        $this->assertSame('order.docx', $this->composition(presetCode: '', orderNumber: '')->downloadName());
    }

    private function issuer(): OrderCompositionIssuer
    {
        return app(OrderCompositionIssuer::class);
    }

    /**
     * @param  array<string,mixed>  $fields
     */
    private function composition(
        string $presetCode = 'leave',
        ?int $personnelId = null,
        ?int $candidateId = null,
        ?int $hireStructureId = null,
        ?int $hirePositionId = null,
        array $fields = [],
        string $orderNumber = '100-M',
        string $orderDate = '',
    ): OrderComposition {
        return new OrderComposition($presetCode, $personnelId, $candidateId, $hireStructureId, $hirePositionId, $fields, $orderNumber, $orderDate, 'Bakı şəhəri');
    }

    private function leaveTemplate(): OrderWordTemplate
    {
        $this->makeMaster('order-templates/leave.docx', '${var_1} ${var_2}');

        return OrderWordTemplate::create([
            'code' => 'leave',
            'label' => 'Məzuniyyət',
            'docx_path' => 'order-templates/leave.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name_dative', 'field' => null],
                ['token' => 'var_2', 'label' => 'Başlama tarixi', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'text']],
            ],
            'is_active' => true,
        ]);
    }

    private function hireTemplate(): OrderWordTemplate
    {
        $this->makeMaster('order-templates/hire.docx', '${var_1} ${var_2}');

        return OrderWordTemplate::create([
            'code' => 'hire',
            'label' => 'İşə qəbul',
            'effect' => 'hire',
            'docx_path' => 'order-templates/hire.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Tarix', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
            ],
            'is_active' => true,
        ]);
    }

    private function makeMaster(string $relative, string $text): void
    {
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText($text);
        $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::disk('local')->put($relative, (string) file_get_contents($tmp));
        @unlink($tmp);
    }

    private function makeCandidate(Structure $structure): Candidate
    {
        return Candidate::query()->create([
            'surname' => 'Hüseynov', 'name' => 'Elçin', 'patronymic' => 'Vüqar', 'height' => 178,
            'structure_id' => $structure->id, 'status_id' => 30, 'gender' => 1, 'birthdate' => '1995-01-01',
        ]);
    }

    private function makePersonnel(): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
        $position = Position::query()->create(['name' => 'operator']);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Bayramov',
            'name' => 'Ruslan',
            'patronymic' => 'Bəxtiyar',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
