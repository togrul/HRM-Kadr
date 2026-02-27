<?php

namespace App\Services\Orders;

use App\Models\OrderGenerationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Throwable;

class OrderTemplateRenderer
{
    private ?bool $generationLogAvailable = null;

    public function render(string $storedTemplatePath, array $scalarValues, array $rows, string $outputBaseName, array $context = []): string
    {
        $renderId = (string) Str::uuid();
        $startedAt = microtime(true);
        $generationLog = null;
        $baseContext = [
            'render_id' => $renderId,
            'template_path' => $storedTemplatePath,
            'row_count' => count($rows),
            ...$context,
        ];

        try {
            PhpWordSettings::setOutputEscapingEnabled(true);

            $resolvedTemplatePath = $this->resolveTemplatePath($storedTemplatePath);
            $generationLog = $this->createGenerationLog($renderId, $baseContext);

            Log::info('orders.template.render.start', [
                ...$baseContext,
                'resolved_template_path' => $resolvedTemplatePath,
            ]);

            $templateProcessor = new TemplateProcessor($resolvedTemplatePath);

            foreach ($scalarValues as $key => $value) {
                $templateProcessor->setValue($this->normalizeMacroName((string) $key), $this->normalizeValue($value));
            }

            if (empty($rows)) {
                $templateProcessor->cloneBlock('content', 0, true, false, []);
            } else {
                $templateProcessor->cloneBlock('content', count($rows), true, true, null);

                foreach ($rows as $index => $row) {
                    $rowNo = $index + 1;

                    foreach ($row as $macro => $value) {
                        $templateProcessor->setValue($this->rowMacroName((string) $macro, $rowNo), $this->normalizeValue($value));
                    }

                    // Nested newline block replacement must be row-scoped to avoid XML corruption.
                    $templateProcessor->setValue('newline#' . $rowNo, '');
                    $templateProcessor->setValue('/newline#' . $rowNo, '');
                }
            }

            $outputPath = $this->buildOutputPath($outputBaseName);
            $templateProcessor->saveAs($outputPath);

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('DOCX file was not generated or is empty.');
            }

            Log::info('orders.template.render.success', [
                ...$baseContext,
                'output_path' => $outputPath,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'size_bytes' => filesize($outputPath),
            ]);
            $this->markGenerationSuccess(
                $generationLog,
                (int) round((microtime(true) - $startedAt) * 1000),
                $outputPath,
                [
                    ...$baseContext,
                    'size_bytes' => filesize($outputPath),
                ],
            );

            return $outputPath;
        } catch (Throwable $e) {
            Log::error('orders.template.render.failed', [
                ...$baseContext,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $e->getMessage(),
            ]);
            $this->markGenerationFailure(
                $generationLog,
                (int) round((microtime(true) - $startedAt) * 1000),
                $e,
                $baseContext
            );

            throw $e;
        }
    }

    private function createGenerationLog(string $renderId, array $context): ?OrderGenerationLog
    {
        if (! $this->canUseGenerationLog()) {
            return null;
        }

        try {
            return OrderGenerationLog::query()->create([
                'render_id' => $renderId,
                'order_log_id' => $this->toNullableInt($context['order_log_id'] ?? null),
                'order_type_id' => $this->toNullableInt($context['order_type_id'] ?? null),
                'order_template_version_id' => $this->toNullableInt($context['template_version_id'] ?? null),
                'status' => 'started',
                'context' => $context,
            ]);
        } catch (Throwable $e) {
            Log::warning('orders.template.render.log_start_failed', [
                'render_id' => $renderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function markGenerationSuccess(?OrderGenerationLog $log, int $durationMs, string $outputPath, array $context): void
    {
        if (! $log) {
            return;
        }

        try {
            $log->update([
                'status' => 'success',
                'duration_ms' => $durationMs,
                'output_path' => $outputPath,
                'context' => $context,
            ]);
        } catch (Throwable $e) {
            Log::warning('orders.template.render.log_success_update_failed', [
                'render_id' => $log->render_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function markGenerationFailure(?OrderGenerationLog $log, int $durationMs, Throwable $e, array $context): void
    {
        if (! $log) {
            return;
        }

        try {
            $log->update([
                'status' => 'failed',
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
                'context' => $context,
            ]);
        } catch (Throwable $updateException) {
            Log::warning('orders.template.render.log_failed_update_failed', [
                'render_id' => $log->render_id,
                'error' => $updateException->getMessage(),
            ]);
        }
    }

    private function canUseGenerationLog(): bool
    {
        if ($this->generationLogAvailable !== null) {
            return $this->generationLogAvailable;
        }

        try {
            $this->generationLogAvailable = Schema::hasTable('order_generation_logs');
        } catch (Throwable) {
            $this->generationLogAvailable = false;
        }

        return $this->generationLogAvailable;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return str_replace(["\r\n", "\r"], "\n", (string) $value);
        }

        return str_replace(["\r\n", "\r"], "\n", json_encode($value, JSON_UNESCAPED_UNICODE) ?: '');
    }

    private function normalizeMacroName(string $macro): string
    {
        $name = trim($macro);
        $name = preg_replace('/^\$\{(.+)\}$/', '$1', $name) ?: $name;
        $name = ltrim(trim($name), '$');

        return trim($name);
    }

    private function rowMacroName(string $macro, int $rowNo): string
    {
        $base = $this->normalizeMacroName($macro);
        $base = preg_replace('/#\d+$/', '', $base) ?: $base;

        return $base.'#'.$rowNo;
    }

    private function buildOutputPath(string $outputBaseName): string
    {
        $outputDir = storage_path('app/tmp/orders');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $safeBaseName = trim((string) preg_replace('/[\\\\\\/:*?"<>|]/', '_', $outputBaseName));
        if ($safeBaseName === '') {
            $safeBaseName = 'order';
        }

        $filename = sprintf('%s_%s.docx', $safeBaseName, now()->format('d.m.Y H-i-s'));

        return $outputDir . DIRECTORY_SEPARATOR . $filename;
    }

    private function resolveTemplatePath(string $storedPath): string
    {
        if (is_file($storedPath)) {
            return $storedPath;
        }

        $path = ltrim($storedPath, '/');
        $candidates = [
            storage_path($path),
            storage_path('app/' . $path),
            public_path('storage/' . $path),
            base_path('storage/' . $path),
            base_path($path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("Order template file not found: {$storedPath}");
    }
}
