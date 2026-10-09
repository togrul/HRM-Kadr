<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\DocxPlaceholderParser;
use App\Modules\Orders\Application\Document\LegacyOrderTemplateNeutralizer;
use App\Modules\Orders\Application\Document\OrderTemplateDocxBuilder;
use App\Modules\Orders\Application\Document\OrderWordTemplateVersioner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Moves the standard business-trip order («Ezamiyyət», code `ezamiyyet`) of an existing
 * install onto the multi-participant layout: one order for a whole team, a participants
 * table repeated per person, shared dates each person may override, plus the trip kind
 * (domestic / abroad) and the funding source.
 *
 * Only a template that is still exactly what the earlier catalogue seeded is replaced:
 * effect business_trip, the seeded manual placeholders with their roles, and a master whose
 * text (tokens shown as [labels]) matches the earlier seed paragraph for paragraph — also
 * when it still carries the pre-neutralization wording. A template HR reworked is reported
 * as edited and left alone. The replaced master is archived as a template version, and the
 * field values of every order already issued on it are re-keyed from the old tokens to the
 * new ones by placeholder label, so drafts reopen and approved orders reverse as before.
 * Idempotent: an already multi-participant template is reported as current.
 */
class StandardBusinessTripTemplateUpgrader
{
    public const CODE = 'ezamiyyet';

    /** The spec the earlier catalogue seeded (single employee, no table). */
    public const PREVIOUS_SPEC = [
        'city' => 'Bakı şəhəri',
        'subject' => 'Əməkdaşın ezamiyyətə göndərilməsi haqqında',
        'preamble' => 'İşin zərurətini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 179-cu maddəsinin 2-ci hissəsinin “x” bəndini və 181-ci maddəsini rəhbər tutaraq',
        'clauses' => [
            '[İş yeri] [Vəzifə] [İşçi] [Ezamiyyətin məqsədi] məqsədilə [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək [Ezamiyyə yeri] ezamiyyətə göndərilsin.',
            'Ezamiyyətə gediş-gəliş [Nəqliyyat] ilə təşkil edilsin, işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
            'Ezamiyyə xərcləri qanunvericiliyə uyğun ödənilsin. [Ezamiyyə xərcləri (gündəlik)]',
            'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
        ],
        'basis' => '[Əsas mətni]',
    ];

    /** The earlier seed's manual placeholders => effect role (null = none). */
    public const PREVIOUS_MANUAL = [
        'Ezamiyyətin məqsədi' => 'purpose',
        'Başlama tarixi' => 'start_date',
        'Bitmə tarixi' => 'end_date',
        'Ezamiyyə yeri' => 'location',
        'Nəqliyyat' => 'transport',
        'İşə başlama tarixi' => 'return_date',
        'Ezamiyyə xərcləri (gündəlik)' => 'per_diem',
        'Əsas mətni' => null,
    ];

    public function __construct(
        private readonly DocxPlaceholderParser $parser,
        private readonly OrderTemplateDocxBuilder $builder,
        private readonly OrderWordTemplateVersioner $versioner,
    ) {}

    /**
     * @return array{updated:list<string>,current:list<string>,edited:list<string>,missing:list<string>,orders_rekeyed:int}
     */
    public function run(bool $dryRun = false): array
    {
        $result = ['updated' => [], 'current' => [], 'edited' => [], 'missing' => [], 'orders_rekeyed' => 0];
        $template = OrderWordTemplate::query()->where('code', self::CODE)->first();

        if ($template === null || ! $template->docx_path || ! Storage::disk('local')->exists((string) $template->docx_path)) {
            $result['missing'][] = self::CODE;

            return $result;
        }

        if ($template->isMultiParticipant()) {
            $result['current'][] = self::CODE;

            return $result;
        }

        $variables = array_values((array) $template->variables);
        if ($template->effect !== 'business_trip' || ! $this->hasSeededMapping($variables) || ! $this->hasSeededText($template, $variables)) {
            $result['edited'][] = self::CODE;

            return $result;
        }

        $result['updated'][] = self::CODE;
        if ($dryRun) {
            return $result;
        }

        $oldLabels = $this->tokenToLabel($variables);
        $this->versioner->archive($template);
        Artisan::call('orders:seed-word-templates', ['--only' => self::CODE]);

        $newTokens = array_flip($this->tokenToLabel(array_values((array) $template->fresh()?->variables)));
        $result['orders_rekeyed'] = DB::transaction(fn (): int => $this->rekeyOrders($oldLabels, $newTokens));

        return $result;
    }

    /**
     * The seeded manual placeholders, each on its seeded role (none bound elsewhere).
     *
     * @param  list<array<string,mixed>>  $variables
     */
    private function hasSeededMapping(array $variables): bool
    {
        $manual = [];
        foreach ($variables as $variable) {
            if (($variable['source'] ?? 'manual') !== 'manual') {
                continue;
            }

            $label = (string) ($variable['label'] ?? '');
            if (! array_key_exists($label, self::PREVIOUS_MANUAL) || ($variable['effect_role'] ?? null) !== self::PREVIOUS_MANUAL[$label]) {
                return false;
            }
            $manual[$label] = true;
        }

        return count($manual) === count(self::PREVIOUS_MANUAL);
    }

    /**
     * The stored master reads exactly like the earlier seed (blank lines aside), its legacy
     * wording mapped as the neutralizer maps it.
     *
     * @param  list<array<string,mixed>>  $variables
     */
    private function hasSeededText(OrderWordTemplate $template, array $variables): bool
    {
        $labels = $this->tokenToLabel($variables);
        $stored = array_map(
            fn (string $paragraph): string => (string) preg_replace_callback(
                '/\$\{([A-Za-z0-9_]+)\}/',
                fn (array $m): string => isset($labels[$m[1]]) ? '['.$labels[$m[1]].']' : $m[0],
                $paragraph,
            ),
            $this->parser->paragraphs(Storage::disk('local')->path((string) $template->docx_path)),
        );

        $built = $this->builder->build(self::PREVIOUS_SPEC + ['organization' => '[Təşkilatın adı]']);
        try {
            $expected = $this->compact($this->parser->paragraphs($built));
        } finally {
            @unlink($built);
        }

        if ($this->compact($stored) === $expected) {
            return true;
        }

        $legacy = LegacyOrderTemplateNeutralizer::LITERALS
            + array_map(fn (string $label): string => '['.$label.']', LegacyOrderTemplateNeutralizer::PLACEHOLDERS);

        return $this->compact(array_map(fn (string $p): string => strtr($p, $legacy), $stored)) === $expected;
    }

    /**
     * Re-key every order issued on the template (trashed ones too) from the old tokens to the
     * new ones by placeholder label; a value whose label is gone is kept under its old key.
     *
     * @param  array<string,string>  $oldLabels  old token => label
     * @param  array<string,string>  $newTokens  label => new token
     */
    private function rekeyOrders(array $oldLabels, array $newTokens): int
    {
        $count = 0;

        OrderLog::withTrashed()
            ->where('template_snapshot->template_code', self::CODE)
            ->chunkById(200, function ($orders) use ($oldLabels, $newTokens, &$count): void {
                foreach ($orders as $order) {
                    $snapshot = (array) $order->template_snapshot;
                    $fields = [];
                    foreach ((array) ($snapshot['fields'] ?? []) as $token => $value) {
                        $label = $oldLabels[$token] ?? null;
                        $fields[$label !== null && isset($newTokens[$label]) ? $newTokens[$label] : $token] = $value;
                    }

                    $snapshot['fields'] = $fields;
                    $order->forceFill(['template_snapshot' => $snapshot])->saveQuietly();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @param  list<array<string,mixed>>  $variables
     * @return array<string,string> token => label
     */
    private function tokenToLabel(array $variables): array
    {
        $map = [];
        foreach ($variables as $variable) {
            if (isset($variable['token'], $variable['label'])) {
                $map[(string) $variable['token']] = (string) $variable['label'];
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $paragraphs
     * @return list<string>
     */
    private function compact(array $paragraphs): array
    {
        return array_values(array_filter(array_map('trim', $paragraphs), fn (string $p): bool => $p !== ''));
    }
}
