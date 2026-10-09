<?php

use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\User;
use App\Modules\Orders\Application\Document\PdfConverter;
use App\Modules\Orders\Infrastructure\Document\OrderFinalPdfService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\AllOrders;
use App\Services\StructureService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * An approved order keeps an immutable final PDF (path + SHA-256), rendered once at
 * approval. Without a converter approval still succeeds and the copy is backfilled later.
 */
beforeEach(function (): void {
    Storage::fake('local');
    config(['orders.final_pdf.on_approval' => true]);

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
});

/** A converter double: "converts" by writing a PDF-looking file whose bytes depend on a run counter. */
function fakePdfConverter(bool $available = true): object
{
    $fake = new class($available) implements PdfConverter
    {
        public int $runs = 0;

        public function __construct(public bool $available) {}

        public function isAvailable(): bool
        {
            return $this->available;
        }

        public function convert(string $docxPath): ?string
        {
            $this->runs++;
            $pdf = tempnam(sys_get_temp_dir(), 'pdf_').'.pdf';
            file_put_contents($pdf, '%PDF-1.4 '.basename($docxPath).' run '.$this->runs);

            return $pdf;
        }
    };

    app()->instance(PdfConverter::class, $fake);

    return $fake;
}

function finalPdfOrder(string $no, int $status = 10): OrderLog
{
    $order = OrderLog::query()->create([
        'order_no' => $no,
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => $status,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => ['hire_structure_id' => 7],
    ]);

    Storage::disk('local')->put('order-documents/'.$order->id.'.docx', 'docx bytes');
    $order->update(['template_snapshot' => ['hire_structure_id' => 7, 'docx_path' => 'order-documents/'.$order->id.'.docx']]);

    return $order;
}

it('stores the final pdf with its hash at approval', function (): void {
    fakePdfConverter();
    $order = finalPdfOrder('FP-1');

    app(OrderStatusTransitionService::class)->approve($order);

    $order->refresh();
    expect($order->final_pdf_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($order->final_pdf_path))->toBeTrue()
        ->and($order->final_pdf_sha256)->toBe(hash('sha256', Storage::disk('local')->get($order->final_pdf_path)));
});

it('approves without a converter, leaves the pdf empty and logs it', function (): void {
    fakePdfConverter(available: false);
    Log::spy();
    $order = finalPdfOrder('FP-2');

    app(OrderStatusTransitionService::class)->approve($order);

    $order->refresh();
    expect((int) $order->status_id)->toBe(20)
        ->and($order->final_pdf_path)->toBeNull();
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message): bool => $message === 'orders.final_pdf.converter_unavailable')->once();
});

it('never overwrites the final copy — not on a second capture, not after revert and re-approval', function (): void {
    $converter = fakePdfConverter();
    $order = finalPdfOrder('FP-3');
    $transitions = app(OrderStatusTransitionService::class);

    $transitions->approve($order);
    $path = $order->fresh()->final_pdf_path;
    $hash = $order->fresh()->final_pdf_sha256;
    $bytes = Storage::disk('local')->get($path);

    expect(app(OrderFinalPdfService::class)->capture($order->fresh()))->toBe($path);

    $transitions->revert($order->fresh(), 'Səhv tərtib edilib');
    $transitions->approve($order->fresh());

    $order->refresh();
    expect($order->final_pdf_path)->toBe($path)
        ->and($order->final_pdf_sha256)->toBe($hash)
        ->and(Storage::disk('local')->get($path))->toBe($bytes)
        ->and($converter->runs)->toBe(1);
});

it('backfills missing final pdfs with the artisan command', function (): void {
    fakePdfConverter(available: false);
    $order = finalPdfOrder('FP-4');
    app(OrderStatusTransitionService::class)->approve($order);
    $pending = finalPdfOrder('FP-5');
    expect($order->fresh()->final_pdf_path)->toBeNull();

    fakePdfConverter();
    expect(Artisan::call('orders:render-final-pdfs'))->toBe(0);

    expect($order->fresh()->final_pdf_path)->not->toBeNull()
        ->and($pending->fresh()->final_pdf_path)->toBeNull();
});

it('downloads the stored final copy as pdf for users who may export', function (): void {
    fakePdfConverter();
    $order = finalPdfOrder('FP/6');
    app(OrderStatusTransitionService::class)->approve($order);

    $user = User::factory()->create();
    $user->givePermissionTo([Permission::findOrCreate('show-orders', 'web'), Permission::findOrCreate('export-orders', 'web')]);
    $this->actingAs($user);

    Livewire::test(AllOrders::class)
        ->assertSee(__('orders::order_list.actions.download_pdf'))
        ->call('downloadPdf', 'FP/6')
        ->assertFileDownloaded('FP-6.pdf');

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('show-orders');
    $this->actingAs($viewer);
    Livewire::test(AllOrders::class)->call('downloadPdf', 'FP/6')->assertForbidden();
});

it('falls back to converting on the fly and reports when that is impossible', function (): void {
    fakePdfConverter(available: false);
    $order = finalPdfOrder('FP-7');
    app(OrderStatusTransitionService::class)->approve($order);

    $user = User::factory()->create();
    $user->givePermissionTo([Permission::findOrCreate('show-orders', 'web'), Permission::findOrCreate('export-orders', 'web')]);
    $this->actingAs($user);

    Livewire::test(AllOrders::class)
        ->call('downloadPdf', 'FP-7')
        ->assertDispatched('orderError', __('orders::order_list.messages.pdf_unavailable'));

    fakePdfConverter();
    Livewire::test(AllOrders::class)
        ->call('downloadPdf', 'FP-7')
        ->assertFileDownloaded('FP-7.pdf');

    // The first successful conversion became the immutable final copy.
    expect($order->fresh()->final_pdf_path)->not->toBeNull();
});
