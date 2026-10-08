<?php

namespace App\Modules\Orders\Application\Document;

use App\Console\Commands\SeedOrderWordTemplatesCommand;
use App\Models\OrderWordTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Brings the standard order templates already stored on an install up to the current
 * catalogue: removes one customer's company name and employee names that earlier seeds
 * baked into the text, and corrects the Labour Code article references.
 *
 * Only an UNEDITED template is touched. "Unedited" means: the stored master's text —
 * with every ${token} shown as its [label] and the known legacy literals mapped to their
 * replacements — is paragraph-for-paragraph identical to what the current seeder builds
 * for that code. A template HR re-uploaded or reworded in the designer differs and is left
 * alone (reported as edited).
 *
 * The master is rewritten in place (run-preserving literal rewrite), so existing ${token}
 * names keep their meaning and drafts that already hold field values stay valid. A legacy
 * literal that becomes a placeholder (company header, static signatory) gets a new token
 * appended to the variable mapping. The previous master is archived as a template version.
 */
class LegacyOrderTemplateNeutralizer
{
    /** Legacy literal => current text. */
    public const LITERALS = [
        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Bağırov Səbuhi bu əmrdən' => 'Mühasibatlıq bu əmrdən',
        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən' => 'Mühasibatlıq bu əmrdən',
        '“Dinçer və Carçıoğlu” Birgə Müəssisəsinin təsdiq edilmiş yeni təşkilati strukturunu' => 'Təşkilatın təsdiq edilmiş yeni təşkilati strukturunu',
        'Əmrin surəti “Dinçer və Carçıoğlu” Birgə Müəssisəsinin bütün struktur bölmələrinə göndərilsin.' => 'Əmrin surəti bütün struktur bölmələrinə göndərilsin.',
        // Labour Code references corrected against the official text.
        'Əmək Məcəlləsinin 138-ci maddəsinin 2-ci hissəsini rəhbər tutaraq' => 'Əmək Məcəlləsinin 114-cü və 131-ci maddələrini, 138-ci maddəsinin 1-ci hissəsini rəhbər tutaraq',
        'Əmək Məcəlləsinin 124-cü maddəsinin 3-cü hissəsini rəhbər tutaraq' => 'Əmək Məcəlləsinin 123-cü və 124-cü maddələrini rəhbər tutaraq',
        'Əmək Məcəlləsinin 178-ci maddəsini rəhbər tutaraq' => 'Əmək Məcəlləsinin 179-cu maddəsinin 2-ci hissəsinin “x” bəndini və 181-ci maddəsini rəhbər tutaraq',
        'Əmək Məcəlləsinin 159-cu maddəsini rəhbər tutaraq' => 'Əmək Məcəlləsinin 61-ci və 162-ci maddələrini rəhbər tutaraq',
        'Əmək Məcəlləsinin 186-cı maddəsini rəhbər tutaraq' => 'Əmək Məcəlləsinin 186-cı və 187-ci maddələrini rəhbər tutaraq',
        // Oldest seeds printed a fixed signatory over three lines.
        'təşkilati idarəetmə və' => '',
        'kommunikasiyalar üzrə müavini' => '',
    ];

    /** Legacy literal => placeholder label that now stands in its place. */
    public const PLACEHOLDERS = [
        '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ' => 'Təşkilatın adı',
        'Baş direktorun İnsan resursları,' => 'İmzalayanın vəzifəsi',
        'Sübhan İsmayılov' => 'İmzalayan',
    ];

    /** Placeholder label => automatic variable key. */
    private const AUTO_KEYS = [
        'Təşkilatın adı' => 'system.organization_name',
        'İmzalayanın vəzifəsi' => 'system.signatory_title',
        'İmzalayan' => 'system.signatory_full_name',
    ];

    public function __construct(
        private readonly DocxPlaceholderParser $parser,
        private readonly OrderTemplateDocxBuilder $builder,
        private readonly OrderWordTemplateVersioner $versioner,
    ) {}

    /**
     * With $dryRun nothing is written; 'updated' then lists the codes that would change.
     *
     * @return array{updated: list<string>, current: list<string>, edited: list<string>, missing: list<string>}
     */
    public function run(bool $dryRun = false): array
    {
        $result = ['updated' => [], 'current' => [], 'edited' => [], 'missing' => []];
        $catalogue = app(SeedOrderWordTemplatesCommand::class)->catalogue();

        $templates = OrderWordTemplate::query()->whereIn('code', array_keys($catalogue))->orderBy('code')->get();

        foreach ($templates as $template) {
            $code = (string) $template->code;
            $disk = Storage::disk('local');

            if (! $template->docx_path || ! $disk->exists((string) $template->docx_path)) {
                $result['missing'][] = $code;

                continue;
            }

            $path = $disk->path((string) $template->docx_path);
            $variables = array_values((array) $template->variables);
            $stored = $this->bracketed($this->parser->paragraphs($path), $variables);
            $expected = $this->expected($catalogue[$code]['spec']);

            if ($this->compact($stored) === $expected) {
                $result['current'][] = $code;

                continue;
            }

            $placeholderMap = array_map(fn (string $label): string => '['.$label.']', self::PLACEHOLDERS);
            if ($this->compact(array_map(fn (string $p): string => strtr($p, self::LITERALS + $placeholderMap), $stored)) !== $expected) {
                $result['edited'][] = $code;

                continue;
            }

            if (! $dryRun) {
                $this->rewrite($template, $path, $variables);
            }
            $result['updated'][] = $code;
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     */
    private function rewrite(OrderWordTemplate $template, string $path, array $variables): void
    {
        $map = self::LITERALS;
        $plain = implode("\n", $this->parser->paragraphs($path));

        foreach (self::PLACEHOLDERS as $literal => $label) {
            if (! str_contains($plain, $literal)) {
                continue;
            }

            $token = $this->tokenFor($label, $variables);
            $map[$literal] = '${'.$token.'}';
        }

        $this->versioner->archive($template);

        $tmp = tempnam(sys_get_temp_dir(), 'ordertpl_fix_').'.docx';
        $this->parser->rewriteLiterals($path, $map, $tmp);
        File::copy($tmp, $path);
        @unlink($tmp);

        $template->forceFill(['variables' => array_values($variables)])->save();
    }

    /**
     * The token already mapped to $label, or a new automatic variable appended for it.
     *
     * @param  array<int, array<string, mixed>>  $variables
     */
    private function tokenFor(string $label, array &$variables): string
    {
        foreach ($variables as $variable) {
            if (($variable['label'] ?? null) === $label && isset($variable['token'])) {
                return (string) $variable['token'];
            }
        }

        $next = 1;
        foreach ($variables as $variable) {
            if (preg_match('/^var_(\d+)$/', (string) ($variable['token'] ?? ''), $m) === 1) {
                $next = max($next, (int) $m[1] + 1);
            }
        }

        $token = 'var_'.$next;
        $variables[] = [
            'token' => $token,
            'label' => $label,
            'source' => 'auto',
            'auto_key' => self::AUTO_KEYS[$label],
            'field' => null,
            'effect_role' => null,
        ];

        return $token;
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
     * Paragraph texts without the empty ones (blank spacer lines differ between seed generations).
     *
     * @param  list<string>  $paragraphs
     * @return list<string>
     */
    private function compact(array $paragraphs): array
    {
        return array_values(array_filter(array_map('trim', $paragraphs), fn (string $p): bool => $p !== ''));
    }
}
