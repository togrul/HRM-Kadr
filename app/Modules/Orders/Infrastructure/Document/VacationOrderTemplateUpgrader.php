<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Console\Commands\SeedOrderWordTemplatesCommand;
use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\DocxPlaceholderParser;
use App\Modules\Orders\Application\Document\OrderTemplateDocxBuilder;
use App\Modules\Orders\Application\Document\OrderWordTemplateVersioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Brings the stored vacation order templates up to the work-year ledger:
 *   - annual leave: the "İş ili" field gets the work_year role, so approval deducts the
 *     leave from the work year the order names (ƏM m.138.1);
 *   - unused-leave compensation: the text names the termination of the employment contract
 *     and ƏM m.144.2 instead of the employee's application.
 * Only an unedited template is touched (role still empty / text still the previous seed,
 * compared paragraph by paragraph as LegacyOrderTemplateNeutralizer does); the previous
 * master is archived as a template version. Idempotent.
 */
class VacationOrderTemplateUpgrader
{
    public const ANNUAL = 'emek_mezuniyyeti';

    public const COMPENSATION = 'istifade_olunmamis_mezuniyyet_kompensasiyasi';

    /** The compensation text as the previous catalogue seeded it. */
    public const PREVIOUS_COMPENSATION_SPEC = [
        'city' => 'Bakı şəhəri',
        'subject' => 'İstifadə olunmamış əmək məzuniyyətinə görə kompensasiya ödənilməsi haqqında',
        'preamble' => '[İşçi (yiyəlik)] ərizəsini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
        'clauses' => [
            '[İş yeri] [Vəzifə] [İşçi (yönlük)] [İş ili] iş ilinə görə istifadə olunmamış [Gün sayı] təqvim günü əmək məzuniyyətinə görə pul kompensasiyası ödənilsin.',
            'Mühasibatlıq kompensasiyanın məbləğini orta əmək haqqı əsasında hesablasın və ödənişi təmin etsin.',
        ],
        'basis' => '[Əsas mətni]',
    ];

    /** Previous text => current text (token-free spans, run-preserving rewrite). */
    public const COMPENSATION_LITERALS = [
        ' ərizəsini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq' => ' əmək müqaviləsinə xitam verilməsi ilə əlaqədar, Azərbaycan Respublikası Əmək Məcəlləsinin 144-cü maddəsinin 2-ci hissəsini rəhbər tutaraq',
        ' iş ilinə görə istifadə olunmamış ' => ' iş ilindən başlayaraq istifadə olunmamış ',
    ];

    public function __construct(
        private readonly DocxPlaceholderParser $parser,
        private readonly OrderTemplateDocxBuilder $builder,
        private readonly OrderWordTemplateVersioner $versioner,
    ) {}

    /**
     * @return array{roles:list<string>,texts:list<string>,edited:list<string>,current:list<string>}
     */
    public function run(bool $dryRun = false): array
    {
        $result = ['roles' => [], 'texts' => [], 'edited' => [], 'current' => []];

        $annual = OrderWordTemplate::query()->where('code', self::ANNUAL)->first();
        if ($annual !== null && $annual->effect === 'vacation') {
            $variables = array_values((array) $annual->variables);
            $changed = false;

            foreach ($variables as $i => $variable) {
                if (($variable['field']['type'] ?? null) === 'work_year' && ($variable['effect_role'] ?? null) === null && ! $this->hasRole($variables, 'work_year')) {
                    $variables[$i]['effect_role'] = 'work_year';
                    $changed = true;
                }
            }

            if ($changed) {
                if (! $dryRun) {
                    $annual->forceFill(['variables' => $variables])->save();
                }
                $result['roles'][] = self::ANNUAL;
            }
        }

        $compensation = OrderWordTemplate::query()->where('code', self::COMPENSATION)->first();
        if ($compensation !== null) {
            $outcome = $this->upgradeCompensationText($compensation, $dryRun);
            $result[$outcome][] = self::COMPENSATION;
        }

        return $result;
    }

    /**
     * @return 'texts'|'edited'|'current'
     */
    private function upgradeCompensationText(OrderWordTemplate $template, bool $dryRun): string
    {
        $disk = Storage::disk('local');

        if (! $template->docx_path || ! $disk->exists((string) $template->docx_path)) {
            return 'edited';
        }

        $path = $disk->path((string) $template->docx_path);
        $stored = $this->compact($this->bracketed($this->parser->paragraphs($path), (array) $template->variables));
        $catalogue = app(SeedOrderWordTemplatesCommand::class)->catalogue();

        if ($stored === $this->expected($catalogue[self::COMPENSATION]['spec'])) {
            return 'current';
        }

        if ($stored !== $this->expected(self::PREVIOUS_COMPENSATION_SPEC)) {
            return 'edited';
        }

        if (! $dryRun) {
            DB::transaction(function () use ($template, $path): void {
                $this->versioner->archive($template);

                $tmp = tempnam(sys_get_temp_dir(), 'ordertpl_vac_').'.docx';
                $this->parser->rewriteLiterals($path, self::COMPENSATION_LITERALS, $tmp);
                File::copy($tmp, $path);
                @unlink($tmp);
            });
        }

        return 'texts';
    }

    /**
     * @param  list<array<string,mixed>>  $variables
     */
    private function hasRole(array $variables, string $role): bool
    {
        foreach ($variables as $variable) {
            if (($variable['effect_role'] ?? null) === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $paragraphs
     * @param  array<int, array<string, mixed>>  $variables
     * @return list<string>
     */
    private function bracketed(array $paragraphs, array $variables): array
    {
        $labels = [];
        foreach ($variables as $variable) {
            if (isset($variable['token'], $variable['label'])) {
                $labels[(string) $variable['token']] = (string) $variable['label'];
            }
        }

        return array_map(
            fn (string $paragraph): string => (string) preg_replace_callback(
                '/\$\{([A-Za-z0-9_]+)\}/',
                fn (array $m): string => isset($labels[$m[1]]) ? '['.$labels[$m[1]].']' : $m[0],
                $paragraph,
            ),
            $paragraphs,
        );
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<string>
     */
    private function expected(array $spec): array
    {
        $built = $this->builder->build($spec + ['organization' => '[Təşkilatın adı]']);

        try {
            return $this->compact($this->parser->paragraphs($built));
        } finally {
            @unlink($built);
        }
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
