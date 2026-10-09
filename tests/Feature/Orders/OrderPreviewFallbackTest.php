<?php

use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\OrderWordTemplate;
use App\Models\User;
use App\Modules\Orders\Application\Document\DocxToHtmlRenderer;
use App\Modules\Orders\Application\Document\DocxToPdfConverter;
use App\Modules\Orders\Application\Document\OrderTemplateDocxBuilder;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Orders\Livewire\OrderPreview;
use App\Services\StructureService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;

/**
 * The list's "Önizlə" must show the order even on a host without LibreOffice (the
 * production image has none): the stored .docx falls back to an HTML rendering, and
 * whatever goes wrong is logged instead of silently turning into "could not preview".
 */
beforeEach(function (): void {
    foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
    }

    $this->app->instance(StructureService::class, new class extends StructureService
    {
        public function __construct() {}

        public function getAccessibleStructures(?User $user = null): array
        {
            return [7];
        }
    });

    $this->user = User::factory()->create();
    foreach (['show-orders', 'add-orders'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($this->user);

    Storage::fake('local');
});

/** A converter that behaves like a host without LibreOffice. */
function previewWithoutLibreOffice(): void
{
    app()->instance(DocxToPdfConverter::class, new class extends DocxToPdfConverter
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
}

function previewOrder(User $user, ?string $docxPath): OrderLog
{
    return OrderLog::query()->create([
        'order_id' => null,
        'order_no' => 'PRV-77',
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => 10,
        'creator_id' => $user->id,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => [
            'template_code' => 'odenissiz_mezuniyyet',
            'label' => 'Ödənişsiz məzuniyyət',
            'fields' => [],
            'candidate_id' => 1,
            'hire_structure_id' => 7,
            'hire_position_id' => 2,
            'order_date_text' => '08.10.2026-cı il',
            'docx_path' => $docxPath,
        ],
    ]);
}

function storedOrderDocx(): string
{
    $built = app(OrderTemplateDocxBuilder::class)->build([
        'organization' => 'Nümunə Təşkilat',
        'city' => 'Bakı şəhəri',
        'subject' => 'Ödənişsiz məzuniyyətin verilməsi haqqında',
        'preamble' => 'Əmək Məcəlləsinin 129-cu maddəsini rəhbər tutaraq',
        'clauses' => ['Əməkdaşa ödənişsiz məzuniyyət verilsin.'],
        'basis' => 'Ərizə',
    ]);

    Storage::disk('local')->put('order-documents/77.docx', (string) file_get_contents($built));
    @unlink($built);

    return 'order-documents/77.docx';
}

it('falls back to an HTML rendering of the stored document when LibreOffice is absent', function (): void {
    previewWithoutLibreOffice();
    $order = previewOrder($this->user, storedOrderDocx());

    $component = Livewire::test(OrderPreview::class, ['orderId' => $order->id])
        ->call('loadPdf')
        ->assertSet('pdf', '')
        ->assertDontSee(__('orders::order_list.preview.unavailable'))
        ->assertSee(__('orders::order_list.preview.html_fallback'));

    expect($component->get('html'))
        ->toContain('Ödənişsiz məzuniyyətin verilməsi haqqında')
        ->toContain('Əməkdaşa ödənişsiz məzuniyyət verilsin.');
});

it('logs why the preview failed instead of swallowing it', function (): void {
    previewWithoutLibreOffice();
    Storage::disk('local')->put('order-documents/broken.docx', 'not a word document');
    $order = previewOrder($this->user, 'order-documents/broken.docx');

    Log::spy();

    Livewire::test(OrderPreview::class, ['orderId' => $order->id])
        ->call('loadPdf')
        ->assertSet('html', '')
        ->assertSee(__('orders::order_list.preview.unavailable'));

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $message === 'orders.preview.render_failed'
            && $context['order_id'] === $order->id
            && filled($context['error']))
        ->once();
});

it('logs a stored document that has gone missing from the disk', function (): void {
    previewWithoutLibreOffice();
    $order = previewOrder($this->user, 'order-documents/gone.docx');

    Log::spy();

    Livewire::test(OrderPreview::class, ['orderId' => $order->id])->call('loadPdf');

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message): bool => $message === 'orders.preview.document_missing')
        ->once();
});

it('renders a docx to HTML with the text escaped', function (): void {
    $built = app(OrderTemplateDocxBuilder::class)->build([
        'organization' => 'Təşkilat',
        'city' => 'Bakı',
        'subject' => 'Mövzu',
        'preamble' => 'Giriş',
        'clauses' => ['Bənd'],
        'basis' => 'Əsas',
    ]);

    $html = app(DocxToHtmlRenderer::class)->render($built);
    @unlink($built);

    expect($html)->toContain('<html')->toContain('Mövzu')->not->toContain('<script');
});

it('shows the composer preview as HTML when LibreOffice is absent', function (): void {
    previewWithoutLibreOffice();

    $phpWord = new PhpWord;
    $phpWord->addSection()->addText('Əmr: ${var_1} tarixindən qüvvəyə minsin.');
    $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
    Storage::disk('local')->put('order-templates/preview_only.docx', (string) file_get_contents($tmp));
    @unlink($tmp);

    OrderWordTemplate::create([
        'code' => 'preview_only',
        'label' => 'Önizləmə',
        'docx_path' => 'order-templates/preview_only.docx',
        'variables' => [
            ['token' => 'var_1', 'label' => 'Tarix', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_1', 'type' => 'text']],
        ],
        'is_active' => true,
    ]);

    $component = Livewire::test(OrderComposer::class, ['presetCode' => 'preview_only'])
        ->set('fields', ['var_1' => '19.05.2026'])
        ->call('preview')
        ->assertHasNoErrors()
        ->assertSet('previewPdf', '');

    expect($component->get('previewHtml'))->toContain('19.05.2026 tarixindən qüvvəyə minsin.');
});
