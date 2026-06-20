<?php

namespace Tests\Feature\Orders;

use App\Models\OrderWordTemplate;
use App\Models\User;
use App\Modules\Orders\Livewire\OrderTemplateDesigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The Word-upload order-type designer: authorization, loading an existing template for
 * editing, re-mapping its variables, and code validation. (The parse/normalize of a
 * freshly uploaded .docx is covered by DocxPlaceholderParserTest.)
 */
class OrderTemplateDesignerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_users_without_edit_orders_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(OrderTemplateDesigner::class)->assertForbidden();
    }

    public function test_landing_lists_existing_templates_to_open(): void
    {
        $this->seedTemplate();
        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class)
            ->assertSet('isNew', true)
            ->assertSee(__('orders::order_composer.designer.existing_title'))
            ->assertSee('Məzuniyyət')
            ->assertSeeHtml(route('orders.designer', 'leave'));
    }

    public function test_it_loads_an_existing_template_for_editing(): void
    {
        $this->seedTemplate();
        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class, ['code' => 'leave'])
            ->assertSet('isNew', false)
            ->assertSet('label', 'Məzuniyyət')
            ->assertSet('variables.0.label', 'Tam ad')
            ->assertSet('variables.0.source', 'auto')
            ->assertSet('variables.1.label', 'Başlama tarixi')
            ->assertSet('variables.1.source', 'manual')
            // The redesigned Word-upload UI renders.
            ->assertSee('Word şablonunu yükləyin')
            ->assertSee('Word faylı seçin')
            ->assertSee('Dəyişənlər')
            ->assertSee('Avtomatik')
            ->assertSee('Əl ilə');
    }

    public function test_it_remaps_a_variable_and_saves_without_reupload(): void
    {
        $this->seedTemplate();
        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class, ['code' => 'leave'])
            ->set('variables.0.auto_key', 'employee.full_name_genitive')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('templateAdded');

        $template = OrderWordTemplate::where('code', 'leave')->firstOrFail();
        $this->assertSame('employee.full_name_genitive', $template->variables[0]['auto_key']);
        // The master file is untouched when no new file is uploaded.
        $this->assertSame('order-templates/leave.docx', $template->docx_path);
    }

    public function test_uploading_a_word_file_detects_and_lists_its_placeholders(): void
    {
        $this->actingAs($this->editor());

        $docx = tempnam(sys_get_temp_dir(), 'up_').'.docx';
        $this->makeWordWithPlaceholders($docx);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('sablon.docx', (string) file_get_contents($docx));
        @unlink($docx);

        Livewire::test(OrderTemplateDesigner::class)
            ->set('upload', $file)
            ->assertHasNoErrors()
            ->assertSet('variables.0.label', 'Tam ad')
            ->assertSet('variables.1.label', 'Başlama tarixi')
            // "Tam ad" fuzzy-matches a catalog variable, so it is pre-mapped to auto.
            ->assertSet('variables.0.source', 'auto');
    }

    public function test_it_previews_the_stored_template_as_a_pdf(): void
    {
        if (! app(\App\Services\Orders\Document\DocxToPdfConverter::class)->isAvailable()) {
            $this->markTestSkipped('LibreOffice not available for PDF conversion.');
        }

        $this->actingAs($this->editor());

        // A real ${token} master so the renderer + LibreOffice can process it.
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $phpWord->addSection()->addText('İşçi ${var_1} üçün ${var_2} təyin edilsin.');
        $tmp = tempnam(sys_get_temp_dir(), 'm_').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::disk('local')->put('order-templates/leave.docx', (string) file_get_contents($tmp));
        @unlink($tmp);

        OrderWordTemplate::create([
            'code' => 'leave',
            'label' => 'Məzuniyyət',
            'docx_path' => 'order-templates/leave.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name_dative', 'field' => null],
                ['token' => 'var_2', 'label' => 'Başlama tarixi', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'text']],
            ],
            'is_active' => true,
        ]);

        $component = Livewire::test(OrderTemplateDesigner::class, ['code' => 'leave'])
            ->call('previewTemplate')
            ->assertHasNoErrors();

        $this->assertNotEmpty($component->get('templatePdf'));
    }

    public function test_reuploading_a_template_archives_the_previous_version(): void
    {
        $this->seedTemplate();
        $this->actingAs($this->editor());

        $docx = tempnam(sys_get_temp_dir(), 're_').'.docx';
        $this->makeWordWithPlaceholders($docx);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('yeni.docx', (string) file_get_contents($docx));
        @unlink($docx);

        Livewire::test(OrderTemplateDesigner::class, ['code' => 'leave'])
            ->set('upload', $file)
            ->call('save')
            ->assertHasNoErrors();

        $template = OrderWordTemplate::where('code', 'leave')->firstOrFail();
        $this->assertCount(1, $template->versions);

        $version = $template->versions->first();
        $this->assertSame(1, $version->version);
        Storage::disk('local')->assertExists($version->docx_path);
    }

    public function test_validation_rejects_a_bad_code(): void
    {
        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class)
            ->set('code', 'Bad Code!!')
            ->set('label', 'X')
            ->call('save')
            ->assertHasErrors('code');
    }

    private function seedTemplate(): void
    {
        Storage::disk('local')->put('order-templates/leave.docx', 'dummy');

        OrderWordTemplate::create([
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

    private function makeWordWithPlaceholders(string $path): void
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $section = $phpWord->addSection();
        $section->addText('İşçi: [Tam ad]');
        $section->addText('Məzuniyyətin başlanma tarixi [Başlama tarixi]');
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($path);
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-orders', 'web'));

        return $user;
    }
}
