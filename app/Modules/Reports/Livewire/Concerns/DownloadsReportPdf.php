<?php

namespace App\Modules\Reports\Livewire\Concerns;

use App\Models\User;
use App\Modules\Reports\Application\Pdf\ReportPdfException;
use App\Modules\Reports\Application\Services\ReportFileBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Server-side PDF yükləməsi: payload artıq cari istifadəçinin icazə və strukturları ilə
 * qurulub; burada yalnız PDF-ə çevrilir. Mühərrik yoxdursa və ya çevirmə uğursuzdursa
 * istifadəçi bildiriş alır, səhifə dağılmır.
 */
trait DownloadsReportPdf
{
    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,mixed>  $filters
     */
    protected function downloadReportPdf(array $payload, array $filters, string $basename): ?StreamedResponse
    {
        $builder = app(ReportFileBuilder::class);
        $user = auth()->user();

        try {
            $file = $builder->file($payload, 'pdf', $basename, $builder->meta($filters, $user instanceof User ? (string) $user->name : null));
        } catch (ReportPdfException $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->userMessage());

            return null;
        }

        $content = $file['content'];

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $file['filename'], ['Content-Type' => $file['mime']]);
    }
}
