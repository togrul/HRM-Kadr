<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Modules\Orders\Application\Document\PdfConverter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The immutable final copy of an approved order.
 *
 * At approval the order's final .docx is converted to PDF once and stored with its
 * SHA-256 on the order. That copy is never re-rendered or overwritten afterwards — it is
 * what was approved. Conversion needs LibreOffice; when the host has none (or it fails)
 * approval still succeeds, the gap is logged and `orders:render-final-pdfs` fills it in
 * later.
 */
class OrderFinalPdfService
{
    private const DIRECTORY = 'order-documents/final';

    public function __construct(private readonly PdfConverter $converter) {}

    /** Whether approval should capture the final PDF right away (config orders.final_pdf.on_approval). */
    public static function capturesOnApproval(): bool
    {
        return (bool) config('orders.final_pdf.on_approval', true);
    }

    /** The stored final copy's path on the local disk, or null when there is none (or its file is gone). */
    public function storedPath(OrderLog $order): ?string
    {
        $path = (string) $order->final_pdf_path;

        return $path !== '' && Storage::disk('local')->exists($path) ? $path : null;
    }

    /**
     * Convert the approved order's final .docx and store it as the final copy — once.
     * Returns the stored path (an existing copy is returned untouched), or null when the
     * order has no document or the conversion is not possible here (logged, never thrown).
     */
    public function capture(OrderLog $order): ?string
    {
        if (filled($order->final_pdf_path)) {
            return (string) $order->final_pdf_path;
        }

        if (! $this->isFinalDocxOrder($order)) {
            return null;
        }

        $pdf = $this->convertDocx($order, 'orders.final_pdf');
        if ($pdf === null) {
            return null;
        }

        $contents = (string) file_get_contents($pdf);
        @unlink($pdf);

        $sha256 = hash('sha256', $contents);
        $stored = self::DIRECTORY.'/'.$order->id.'-'.substr($sha256, 0, 16).'.pdf';
        Storage::disk('local')->put($stored, $contents);

        // Fill only while still empty: a concurrent capture can never replace a stored copy.
        $claimed = OrderLog::withTrashed()
            ->whereKey($order->id)
            ->whereNull('final_pdf_path')
            ->update(['final_pdf_path' => $stored, 'final_pdf_sha256' => $sha256]);

        if ($claimed === 0) {
            $winner = (string) OrderLog::withTrashed()->whereKey($order->id)->value('final_pdf_path');
            if ($winner !== $stored) {
                Storage::disk('local')->delete($stored);
            }

            $order->refresh();

            return $winner !== '' ? $winner : null;
        }

        $order->forceFill(['final_pdf_path' => $stored, 'final_pdf_sha256' => $sha256])->syncOriginal();

        activity('orders')
            ->performedOn($order)
            ->withProperties(['order_no' => $order->order_no, 'path' => $stored, 'sha256' => $sha256])
            ->event('final_pdf_stored')
            ->log('order.final_pdf_stored');

        return $stored;
    }

    /**
     * A PDF of an approved order to hand to the user: the stored final copy, else one
     * captured now (and stored, when none was ever recorded), else a one-off conversion
     * of the .docx when the recorded copy's file is missing.
     *
     * @return array{path:string, temporary:bool}|null absolute file path; a temporary one is the caller's to delete
     */
    public function forDownload(OrderLog $order): ?array
    {
        $stored = $this->storedPath($order);

        if ($stored === null && blank($order->final_pdf_path)) {
            $stored = $this->capture($order);
        }

        if ($stored !== null) {
            $absolute = Storage::disk('local')->path($stored);
            $this->verify($order, $absolute);

            return ['path' => $absolute, 'temporary' => false];
        }

        if (filled($order->final_pdf_path)) {
            Log::warning('orders.final_pdf.file_missing', ['order_id' => $order->id, 'path' => $order->final_pdf_path]);
        }

        $pdf = $this->isFinalDocxOrder($order) ? $this->convertDocx($order, 'orders.final_pdf.on_the_fly') : null;

        return $pdf === null ? null : ['path' => $pdf, 'temporary' => true];
    }

    private function isFinalDocxOrder(OrderLog $order): bool
    {
        return (int) $order->status_id === OrderStatusEnum::APPROVED->value
            && (string) $order->template_render_mode === OrderIssueService::RENDER_MODE_DOCX;
    }

    /**
     * @return string|null absolute path of a fresh PDF the caller owns
     */
    private function convertDocx(OrderLog $order, string $logKey): ?string
    {
        $docx = (string) data_get($order->template_snapshot, 'docx_path', '');
        if ($docx === '' || ! Storage::disk('local')->exists($docx)) {
            Log::warning($logKey.'.no_document', ['order_id' => $order->id, 'docx' => $docx]);

            return null;
        }

        if (! $this->converter->isAvailable()) {
            Log::warning($logKey.'.converter_unavailable', ['order_id' => $order->id]);

            return null;
        }

        try {
            $pdf = $this->converter->convert(Storage::disk('local')->path($docx));
        } catch (Throwable $e) {
            Log::warning($logKey.'.failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return null;
        }

        if ($pdf === null || ! is_file($pdf)) {
            Log::warning($logKey.'.failed', ['order_id' => $order->id]);

            return null;
        }

        return $pdf;
    }

    /** A stored copy whose bytes no longer match its recorded hash is served, but flagged. */
    private function verify(OrderLog $order, string $absolute): void
    {
        $expected = (string) $order->final_pdf_sha256;

        if ($expected !== '' && ! hash_equals($expected, (string) hash_file('sha256', $absolute))) {
            Log::critical('orders.final_pdf.checksum_mismatch', ['order_id' => $order->id, 'path' => $order->final_pdf_path]);
        }
    }
}
