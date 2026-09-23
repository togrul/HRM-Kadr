<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Orders\Application\Document\DocxTemplateRenderer;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use App\Modules\Orders\Contracts\OrderDrafter;
use App\Services\Chief\ChiefResolver;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Drafts a pending order for one employee from a Word template without the composer:
 * manual fields are given by their placeholder label, the document is rendered and
 * attached, and the order then waits in /orders for review and approval like any other.
 */
class OrderDraftService implements OrderDrafter
{
    /**
     * The city line printed in the order document header. Document content, not UI text:
     * orders are issued in Azerbaijani regardless of the author's UI locale.
     */
    public const ORGANIZATION_CITY = 'Bakı şəhəri';

    public function __construct(
        private readonly OrderWordTemplateRepository $templates,
        private readonly DocxVariableResolver $resolver,
        private readonly DocxTemplateRenderer $renderer,
        private readonly OrderIssueService $issuer,
        private readonly ChiefResolver $chiefs,
        private readonly AzerbaijaniDateFormatter $dates,
    ) {}

    public function hasTemplate(string $code): bool
    {
        return $this->templates->find($code) !== null;
    }

    /**
     * @param  array<string, string>  $fieldsByLabel  placeholder label → value
     *
     * @throws RuntimeException when the template is not registered
     */
    public function draft(string $templateCode, Personnel $personnel, array $fieldsByLabel, string $orderNumber): OrderLog
    {
        $template = $this->templates->find($templateCode) ?? throw new RuntimeException("Order template [{$templateCode}] is not registered.");

        $fields = collect($template->variables ?? [])
            ->filter(fn (array $variable): bool => $variable['source'] === 'manual')
            ->mapWithKeys(fn (array $variable): array => [$variable['token'] => (string) ($fieldsByLabel[$variable['label']] ?? '')])
            ->all();

        $orderDate = $this->dates->longDate(now());
        $signatory = $this->chiefs->current(now());
        $values = $this->resolver->resolve($template, $personnel, $fields, [
            'system.order_number' => $orderNumber,
            'system.order_date' => $orderDate,
            'system.organization_city' => self::ORGANIZATION_CITY,
            'system.signatory_full_name' => (string) ($signatory['fullname'] ?? ''),
            'system.signatory_title' => (string) ($signatory['title'] ?? ''),
        ]);

        $order = $this->issuer->issueWord([
            'template_code' => $templateCode,
            'label' => $template->label,
            'personnel_id' => $personnel->id,
            'fields' => $fields,
            'order_number' => $orderNumber,
            'order_date' => $orderDate,
            'signatory' => $signatory,
        ]);

        $tmp = $this->renderer->renderToFile($template->docx_path, $values);
        $stored = 'order-documents/'.$order->id.'.docx';
        Storage::disk('local')->put($stored, (string) file_get_contents($tmp));
        @unlink($tmp);

        return $this->issuer->attachUploadedDocx($order, $stored);
    }
}
