<?php

namespace App\Console\Commands;

use App\Modules\Orders\Application\Document\DocxPlaceholderParser;
use App\Modules\Orders\Application\Document\OrderTemplateDocxBuilder;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Registers the customer's order (əmr) catalogue as Word-engine templates.
 *
 * Each entry is a structured spec (organisation chrome, subject, legal preamble,
 * clauses, basis, signatory) with the dynamic parts written as [bracket] placeholders
 * and a mapping of every placeholder to its data source: an automatic employee/system
 * variable (declension-aware), a manual per-order field, or a list-bound lookup. We
 * build a clean .docx from the spec, normalise it to a ${token} master under
 * storage/app/order-templates, and persist the type so it appears in the composer.
 *
 *   php artisan orders:seed-word-templates
 *   php artisan orders:seed-word-templates --only=emek_mezuniyyeti
 *   php artisan orders:seed-word-templates --missing
 */
class SeedOrderWordTemplatesCommand extends Command
{
    protected $signature = 'orders:seed-word-templates
        {--only= : Seed only this template code}
        {--missing : Seed only the codes this install does not have yet (keeps edited templates untouched)}';

    protected $description = 'Build and register the customer order templates in the Word engine';

    /**
     * The header is a placeholder, not a company name: every install is a different
     * company, resolved per order from Admin → Settings (see OrganizationName).
     */
    private const ORGANIZATION = '[Təşkilatın adı]';

    /** Automatic variable labels shared across templates: label => employee/system key. */
    private const AUTO = [
        'Təşkilatın adı' => 'system.organization_name',
        'Əmrin nömrəsi' => 'system.order_number',
        'Tarix' => 'system.order_date',
        'İş yeri' => 'employee.structure_genitive',
        'İş yeri (yönlük)' => 'employee.structure_dative',
        'Vəzifə' => 'employee.position',
        'İşçi' => 'employee.full_name_with_suffix',
        'İşçi (yönlük)' => 'employee.full_name_dative',
        'İşçi (yiyəlik)' => 'employee.full_name_genitive',
        'İşçi (birgəlik)' => 'employee.full_name_instrumental',
        // Signatory resolved per order (permanent chief or active delegate, by date).
        'İmzalayan' => 'system.signatory_full_name',
        'İmzalayanın vəzifəsi' => 'system.signatory_title',
    ];

    public function handle(
        OrderTemplateDocxBuilder $builder,
        DocxPlaceholderParser $parser,
        OrderWordTemplateRepository $repository,
    ): int {
        $only = $this->option('only');
        $count = 0;

        foreach ($this->catalogue() as $code => $template) {
            if ($only && $only !== $code) {
                continue;
            }

            if ($this->option('missing') && $repository->exists($code)) {
                continue;
            }

            // 1) Build a clean bracketed .docx from the spec.
            $spec = $template['spec'] + ['organization' => self::ORGANIZATION];
            $bracketed = $builder->build($spec);

            // 2) Detect placeholders (document order) and map each to its source.
            $labels = $parser->extract($bracketed);
            $manual = $template['manual'] ?? [];

            $labelToToken = [];
            $variables = [];
            foreach ($labels as $i => $label) {
                $token = 'var_'.($i + 1);
                $labelToToken[$label] = $token;
                $variables[] = $this->variable($token, $label, $manual);
            }

            // 3) Normalise → ${token} master and register the type.
            $relative = 'order-templates/'.$code.'.docx';
            $master = Storage::disk('local')->path($relative);
            File::ensureDirectoryExists(dirname($master));
            $parser->normalize($bracketed, $labelToToken, $master);
            @unlink($bracketed);

            $repository->save($code, $template['label'], $template['effect'], $relative, $variables);

            $this->line(sprintf(
                '  <info>✓</info> %-24s %s  <comment>(%d dəyişən)</comment>',
                $code,
                $template['label'],
                count($variables),
            ));
            $count++;
        }

        $this->newLine();
        $this->info("Hazırdır — {$count} əmr şablonu qeydə alındı. /orders → “Yeni əmr”.");

        return self::SUCCESS;
    }

    /**
     * Resolve one placeholder label to a variable definition: an automatic
     * employee/system variable, or a manual field (optionally bound to a lookup list
     * and/or an approval effect role).
     *
     * @param  array<string,array{type:string,role?:string,required?:bool,default?:string}>  $manual  label => field def
     * @return array<string,mixed>
     */
    private function variable(string $token, string $label, array $manual): array
    {
        if (isset(self::AUTO[$label])) {
            return [
                'token' => $token,
                'label' => $label,
                'source' => 'auto',
                'auto_key' => self::AUTO[$label],
                'field' => null,
                'effect_role' => null,
            ];
        }

        $def = $manual[$label] ?? ['type' => 'text'];

        return [
            'token' => $token,
            'label' => $label,
            'source' => 'manual',
            'auto_key' => null,
            'field' => ['key' => $token, 'type' => $def['type']]
                + (isset($def['required']) ? ['required' => $def['required']] : [])
                + (isset($def['default']) ? ['default' => $def['default']] : []),
            'effect_role' => $def['role'] ?? null,
        ];
    }

    /**
     * The order catalogue. Each entry: human label, approval effect, the document spec
     * (with [bracket] placeholders) and the manual-field definitions for the
     * non-automatic placeholders.
     *
     * @return array<string,array{label:string,effect:string,spec:array<string,mixed>,manual?:array<string,array{type:string,role?:string,required?:bool,default?:string}>}>
     */
    public function catalogue(): array
    {
        return [
            // ───────────────────────────── Əmək məzuniyyəti ─────────────────────────────
            'emek_mezuniyyeti' => [
                'label' => 'Əmək məzuniyyəti',
                'effect' => 'vacation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək məzuniyyətinin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 114-cü və 131-ci maddələrini, 138-ci maddəsinin 1-ci hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] vəzifəsində çalışan [İşçi (yönlük)] [İş ili] iş ilinə görə [Gün sayı] təqvim günü müddətində əmək məzuniyyəti verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'İş ili' => ['type' => 'work_year', 'role' => 'work_year'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────────────────── Atalıq məzuniyyəti ───────────────────────────
            // ƏM m.125.4 — sosial məzuniyyət: illik əmək məzuniyyəti balansından çıxılmır.
            'ataliq_mezuniyyeti' => [
                'label' => 'Atalıq məzuniyyəti',
                'effect' => 'social_leave',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Atalıq məzuniyyətinin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 125-ci maddəsinin 4-cü hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Gün sayı] təqvim günü müddətinə ödənişli atalıq məzuniyyəti verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Gün sayı' => ['type' => 'number', 'role' => 'days', 'default' => '14'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────────── Təhsil məzuniyyəti ────────────────────────────
            // ƏM m.112.1(c), m.123 — təhsil məzuniyyəti: illik əmək məzuniyyəti balansından çıxılmır.
            'tehsil_mezuniyyeti' => [
                'label' => 'Təhsil məzuniyyəti',
                'effect' => 'education_leave',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Ödənişli təhsil məzuniyyətinin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 123-cü maddəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə], [Təhsil məlumatı] [İşçi (yönlük)] [Gün sayı] təqvim günü müddətində ödənişli təhsil məzuniyyəti verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Təhsil məlumatı' => ['type' => 'text'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────────── Ödənişsiz məzuniyyət ──────────────────────────
            // ƏM m.128–130 — ödənişsiz məzuniyyət: illik əmək məzuniyyəti balansından çıxılmır.
            'odenissiz_mezuniyyet' => [
                'label' => 'Ödənişsiz məzuniyyət',
                'effect' => 'unpaid_leave',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Ödənişsiz məzuniyyətin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 129-cu maddəsinin 1-ci hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)], [Səbəb], [Başlama tarixi]-[Bitmə tarixi] tarixləri ödənişsiz məzuniyyət günləri hesab edilsin.',
                        'İşə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri nəzərə alsın.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Səbəb' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────────────────── Hərbi toplantı ──────────────────────────────
            // Approval files the days as a paid absence (puantaj code HT).
            'herbi_toplanti' => [
                'label' => 'Hərbi toplantıda iştirak',
                'effect' => 'paid_absence',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Hərbi toplantıda iştirak barədə',
                    'preamble' => 'Səfərbərlik və Hərbi Xidmətə Çağırış üzrə Dövlət Xidmətinin [Hərbi idarə] çağırış vərəqəsini nəzərə alaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yiyəlik)], Azərbaycan Respublikası Əmək Məcəlləsinin 179-cu maddəsinin 2-ci hissəsinin “g” bəndinə əsasən, orta əmək haqqı ödənilməklə, [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək, [Gün sayı] təqvim günü müddətinə, [Toplantı yeri] keçiriləcək hərbi toplantıda iştirakına icazə verilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Hərbi idarə' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Toplantı yeri' => ['type' => 'text'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────────────────── Pul mükafatı (KPI) ───────────────────────────
            'pul_mukafati' => [
                'label' => 'Pul mükafatı',
                'effect' => 'award',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Pul mükafatı verilməsi haqqında',
                    'preamble' => 'Xidməti fəaliyyətin qiymətləndirilməsinin (KPI) yekunlarını rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi] [Mükafatın səbəbi] [Məbləğ] manat məbləğində pul mükafatı ilə mükafatlandırılsın.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Mükafatın səbəbi' => ['type' => 'text', 'role' => 'reason'],
                    'Məbləğ' => ['type' => 'number', 'role' => 'amount'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────────────────────── İşə qəbul ───────────────────────────────
            'ise_qebul' => [
                'label' => 'İşə qəbul (Əmək müqaviləsi)',
                'effect' => 'hire',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək müqaviləsinin rəsmiləşdirilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikasının Əmək Məcəlləsinin 81-ci maddəsinin 1-ci hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İşçi] [İşə qəbul tarixi] tarixindən [İş yeri (yönlük)] [Vəzifə] peşəsinə qəbul edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'İşə qəbul tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────────────────── Soyadın dəyişdirilməsi ─────────────────────────
            'soyad_deyisme' => [
                'label' => 'Soyadın dəyişdirilməsi',
                'effect' => 'surname_change',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Soyadın dəyişdirilməsi haqqında',
                    'preamble' => '[İşçi (yiyəlik)] ərizəsini və Şəxsiyyət vəsiqəsinin dəyişdirilməsini nəzərə alaraq',
                    'numbered' => false,
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yiyəlik)] soyadının dəyişdirilərək “[Yeni soyad]” olması nəzərə alınsın.',
                        'İnsan Resursları və Maliyyə, Vergi, Mühasibatlıq departamentləri zəruri sənədlərdə dəyişikliklərin edilməsini və bu əmrdən irəli gələn digər məsələlərin həllini təmin etsinlər.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Yeni soyad' => ['type' => 'text', 'role' => 'new_surname'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────────── Başqa işə keçirilmə ───────────────────────────
            'basqa_ise_kecirilme' => [
                'label' => 'Başqa işə keçirilmə',
                'effect' => 'transfer',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Başqa işə keçirilmə haqqında',
                    'preamble' => 'Təşkilatın təsdiq edilmiş yeni təşkilati strukturunu və ştat cədvəlini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 59-cu maddəsinə əsasən',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi] [Köçürmə tarixi] tarixdən “[Yeni iş yeri]” strukturunun “[Yeni vəzifə]” vəzifəsinə keçirilsin.',
                        'İnsan Resursları və Maliyyə, Vergi, Mühasibatlıq departamentləri bu əmrdən irəli gələn məsələləri mövcud qanunvericiliyə uyğun olaraq həll etsinlər.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Köçürmə tarixi' => ['type' => 'date'],
                    'Yeni iş yeri' => ['type' => 'structure', 'role' => 'new_structure'],
                    'Yeni vəzifə' => ['type' => 'position', 'role' => 'new_position'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────────────── Əmək müqaviləsinə xitam (ərizə) ────────────────────
            'xitam' => [
                'label' => 'Əmək müqaviləsinə xitam',
                'effect' => 'termination',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək müqaviləsinə xitam verilməsi haqqında',
                    'preamble' => '[İş yeri] [Vəzifə] [İşçi (yiyəlik)] ərizəsini nəzərə alaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (birgəlik)] bağlanmış əmək müqaviləsinə Azərbaycan Respublikası Əmək Məcəlləsinin [Maddə] əsasən, [Səbəb], [Xitam tarixi] tarixdən xitam verilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Maddə' => ['type' => 'text'],
                    'Səbəb' => ['type' => 'text'],
                    'Xitam tarixi' => ['type' => 'date', 'role' => 'date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────── Maddə ilə əmək müqaviləsinin ləğvi ────────────────────
            'madde_ile_xitam' => [
                'label' => 'Maddə ilə əmək müqaviləsinin ləğvi',
                'effect' => 'termination',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək müqaviləsinin ləğv edilməsi haqqında',
                    'preamble' => [
                        '[İş yeri] [Vəzifə] [İşçi] barəsində: [Əsaslandırma]',
                        'Yuxarıda göstərilənləri nəzərə alaraq, [Hüquqi əsas] rəhbər tutaraq',
                    ],
                    'numbered' => false,
                    'clauses' => [
                        '[İşçi (birgəlik)] bağlanılmış əmək müqaviləsi, Azərbaycan Respublikası Əmək Məcəlləsinin [Maddə] əsasən, [Səbəb], [Xitam tarixi] tarixdən ləğv edilsin.',
                        '[Məsul şəxs] bu əmrdən irəli gələn məsələləri həll etsin.',
                        'Əmrin surəti bütün struktur bölmələrinə göndərilsin.',
                        'Struktur bölmə rəhbərləri tabeçiliyində olan işçiləri əmrlə tanış etsinlər və gələcəkdə bu cür halların qarşısını almaq üçün zəruri tədbirlər görsünlər.',
                        'Əmrin icrasına nəzarəti öz üzərimdə saxlayıram.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Əsaslandırma' => ['type' => 'text'],
                    'Hüquqi əsas' => ['type' => 'text'],
                    'Maddə' => ['type' => 'text'],
                    'Səbəb' => ['type' => 'text'],
                    'Xitam tarixi' => ['type' => 'date', 'role' => 'date'],
                    'Məsul şəxs' => ['type' => 'text'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────────────── Ezamiyyət ────────────────────────────────
            'ezamiyyet' => [
                'label' => 'Ezamiyyət',
                'effect' => 'business_trip',
                'spec' => [
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
                ],
                'manual' => [
                    'Ezamiyyətin məqsədi' => ['type' => 'text', 'role' => 'purpose'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Ezamiyyə yeri' => ['type' => 'text', 'role' => 'location'],
                    'Nəqliyyat' => ['type' => 'text', 'role' => 'transport'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Ezamiyyə xərcləri (gündəlik)' => ['type' => 'text', 'role' => 'per_diem', 'required' => false],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────── Analıq (hamiləlik və doğuşa görə) məzuniyyəti ─────────────────
            'analiq_mezuniyyeti' => [
                'label' => 'Hamiləlik və doğuşa görə məzuniyyət',
                'effect' => 'social_leave',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Hamiləliyə və doğuşa görə sosial məzuniyyətin verilməsi haqqında',
                    'preamble' => 'Əmək qabiliyyətinin müvəqqəti itirilməsi haqqında vərəqəni nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 125-ci maddəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] hamiləliyə və doğuşa görə [Gün sayı] təqvim günü müddətində sosial məzuniyyət verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    // ƏM m.125: 126 days; 140 for a complicated birth, 180 for two or more children.
                    'Gün sayı' => ['type' => 'number', 'role' => 'days', 'default' => '126'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────────────────── İntizam tənbehi ─────────────────────────────
            // Approval records the sanction in the personnel file; it lapses after the
            // configured term (Admin → Settings, 12 months by default).
            'intizam_tenbehi' => [
                'label' => 'İntizam tənbehi',
                'effect' => 'disciplinary',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'İntizam tənbehinin verilməsi haqqında',
                    'preamble' => '[İş yeri] [Vəzifə] [İşçi (yiyəlik)] [Pozuntunun təsviri] ilə əlaqədar, onun izahatını nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 186-cı və 187-ci maddələrini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Tənbehin növü] intizam tənbehi verilsin.',
                        'Əmr əməkdaşa imza etdirilməklə tanış edilsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Pozuntunun təsviri' => ['type' => 'text', 'role' => 'violation'],
                    'Tənbehin növü' => ['type' => 'text', 'role' => 'sanction_type'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────── Əvəzetmə (vəzifənin icrası) ────────────────────────
            // Approval records the substitution and its extra pay in the Compensation register.
            'evezetme' => [
                'label' => 'Əvəzetmə (vəzifənin müvəqqəti icrası)',
                'effect' => 'substitution',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Vəzifənin müvəqqəti icrasının həvalə edilməsi haqqında',
                    'preamble' => '[Əvəz edilən əməkdaş] işdə olmadığı müddətdə işin fasiləsizliyini təmin etmək məqsədilə, Azərbaycan Respublikası Əmək Məcəlləsinin 61-ci və 162-ci maddələrini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] öz işi ilə yanaşı [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək [Əvəz edilən vəzifə] vəzifəsinin icrası həvalə edilsin.',
                        'Əvəzetmə müddətində [İşçi (yönlük)] vəzifə maaşının [Əlavə ödəniş faizi] faizi miqdarında əlavə ödəniş edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Əvəz edilən əməkdaş' => ['type' => 'personnel', 'role' => 'substituted_employee'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Əvəz edilən vəzifə' => ['type' => 'position', 'role' => 'substituted_position'],
                    'Əlavə ödəniş faizi' => ['type' => 'number', 'role' => 'extra_pay_percent'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────────────── Əmək haqqının dəyişdirilməsi ───────────────────────
            // Approval assigns the new salary from the effective date (Compensation).
            'emek_haqqi_deyisme' => [
                'label' => 'Əmək haqqının dəyişdirilməsi',
                'effect' => 'salary_change',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Vəzifə maaşının dəyişdirilməsi haqqında',
                    'preamble' => '[Dəyişikliyin səbəbi] nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 55-ci maddəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Qüvvəyə minmə tarixi] tarixindən aylıq vəzifə maaşı [Yeni əmək haqqı] manat məbləğində müəyyən edilsin.',
                        'Əmək müqaviləsinə müvafiq əlavə edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Dəyişikliyin səbəbi' => ['type' => 'text'],
                    'Qüvvəyə minmə tarixi' => ['type' => 'date', 'role' => 'effective_date'],
                    'Yeni əmək haqqı' => ['type' => 'number', 'role' => 'new_salary'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────── Uşağa qulluq ilə əlaqədar qismən ödənişli sosial məzuniyyət ───────────────
            // Sosial məzuniyyət: illik əmək məzuniyyəti balansından çıxılmır.
            'usaga_qulluq_mezuniyyeti' => [
                'label' => 'Uşağa qulluq ilə əlaqədar qismən ödənişli sosial məzuniyyət',
                'effect' => 'social_leave',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Uşağa qulluq ilə əlaqədar qismən ödənişli sosial məzuniyyətin verilməsi haqqında',
                    'preamble' => '[İşçi (yiyəlik)] ərizəsini və uşağın doğum haqqında şəhadətnaməsini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Uşaq haqqında məlumat] uşağı üç yaşına çatanadək ona qulluq etmək üçün [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək qismən ödənişli sosial məzuniyyət verilsin.',
                        'İşə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri qanunvericiliyə uyğun həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Uşaq haqqında məlumat' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────────────────────── Fəxri fərman ───────────────────────────────
            // Pulsuz təltif: şəxsi işə yazılır, əmək haqqına ötürülmür.
            'fexri_ferman' => [
                'label' => 'Fəxri fərmanla təltif',
                'effect' => 'award',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Fəxri fərmanla təltif edilməsi haqqında',
                    'preamble' => 'Əməkdaşın xidməti fəaliyyətinin nəticələrini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi] [Təltifin səbəbi] Fəxri fərmanla təltif edilsin.',
                        'Əmrdən çıxarış əməkdaşın şəxsi işinə əlavə edilsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Təltifin səbəbi' => ['type' => 'text', 'role' => 'reason'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────── Əmək funksiyasının müvəqqəti həvaləsi ────────────────────
            'hevale' => [
                'label' => 'Əmək funksiyasının müvəqqəti həvalə edilməsi',
                'effect' => 'none',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək funksiyasının müvəqqəti həvalə edilməsi haqqında',
                    'preamble' => 'İstehsalat zərurətini nəzərə alaraq, [İşçi (yiyəlik)] razılığı ilə, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək [Həvalə edilən vəzifə] vəzifəsi üzrə əmək funksiyasının icrası müvəqqəti həvalə edilsin.',
                        'Həvalə müddətində əməkdaşa görülən işə görə, lakin əvvəlki orta əmək haqqından az olmamaqla əmək haqqı ödənilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Həvalə edilən vəzifə' => ['type' => 'position'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────── Məzuniyyətin başqa vaxta keçirilməsi ────────────────────
            'mezuniyyetin_kecirilmesi' => [
                'label' => 'Məzuniyyətin başqa vaxta keçirilməsi',
                'effect' => 'none',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək məzuniyyətinin başqa vaxta keçirilməsi haqqında',
                    'preamble' => '[İşçi (yiyəlik)] ərizəsini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yiyəlik)] [İş ili] iş ilinə görə [Əvvəlki başlama tarixi] tarixindən verilməli olan əmək məzuniyyəti, [Səbəb], [Yeni başlama tarixi] tarixindən [Yeni bitmə tarixi] tarixinədək olan müddətə keçirilsin.',
                        'Məzuniyyətlərin növbəlilik cədvəlinə müvafiq dəyişiklik edilsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'İş ili' => ['type' => 'work_year'],
                    'Əvvəlki başlama tarixi' => ['type' => 'date'],
                    'Səbəb' => ['type' => 'text'],
                    'Yeni başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Yeni bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────── Qısaldılmış iş vaxtı / əlavə fasilə ────────────────────
            'qisaldilmis_is_vaxti' => [
                'label' => 'Qısaldılmış iş vaxtı / əlavə fasilə',
                'effect' => 'none',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Qısaldılmış iş vaxtının (əlavə fasilənin) müəyyən edilməsi haqqında',
                    'preamble' => '[İşçi (yiyəlik)] ərizəsini və təqdim etdiyi sənədləri nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Səbəb] [Başlama tarixi] tarixindən [Güzəştin təsviri] müəyyən edilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri qanunvericiliyə uyğun həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Səbəb' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date'],
                    'Güzəştin təsviri' => ['type' => 'text'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────────────────────── Donor günü ───────────────────────────────
            // Approval files the rest day as a paid absence (puantaj code DG).
            'donor_gunu' => [
                'label' => 'Donorluq ilə əlaqədar istirahət günü',
                'effect' => 'paid_absence',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Donorluq ilə əlaqədar istirahət günü verilməsi haqqında',
                    'preamble' => '[İşçi (yiyəlik)] ərizəsini və qanvermə haqqında arayışı nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinə və donorluq haqqında qanunvericiliyə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Qanvermə tarixi] tarixində qan verməsi ilə əlaqədar [İstirahət günü] tarixində orta əmək haqqı saxlanılmaqla istirahət günü verilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Qanvermə tarixi' => ['type' => 'date'],
                    'İstirahət günü' => ['type' => 'date', 'role' => 'start_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────── Seçki komissiyasının işində iştirak ────────────────────
            // Approval files the days as a paid absence (puantaj code SK).
            'secki_komissiyasi' => [
                'label' => 'Seçki komissiyasının işində iştirak',
                'effect' => 'paid_absence',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Seçki komissiyasının işində iştirakla əlaqədar əmək funksiyasının icrasından azad edilmə haqqında',
                    'preamble' => '[Seçki komissiyası] müraciətini nəzərə alaraq, Azərbaycan Respublikasının Seçki Məcəlləsinə və Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi], seçki komissiyasının üzvü kimi, [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək ([Gün sayı] təqvim günü) iş yeri və vəzifəsi saxlanılmaqla əmək funksiyasının icrasından azad edilsin.',
                        'Mühasibatlıq bu müddət üçün ödənişi qanunvericiliyə uyğun həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Seçki komissiyası' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────── Mülki müdafiə təlimi ────────────────────────
            // Approval files the days as a paid absence (puantaj code MM).
            'mulki_mudafie' => [
                'label' => 'Mülki müdafiə təlimində iştirak',
                'effect' => 'paid_absence',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Mülki müdafiə təlimində iştirak barədə',
                    'preamble' => '[Təlimi təşkil edən qurum] müraciətini nəzərə alaraq, Azərbaycan Respublikasının mülki müdafiə haqqında qanunvericiliyinə və Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yiyəlik)] [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək ([Gün sayı] təqvim günü) [Təlimin yeri] keçiriləcək mülki müdafiə təlimində iştirakına orta əmək haqqı saxlanılmaqla icazə verilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Təlimi təşkil edən qurum' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Təlimin yeri' => ['type' => 'text'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────── Məzuniyyətdən geri çağırma ────────────────────────
            // Approval ends the current leave the day before the recall date and returns
            // the unused days to the yearly balance.
            'mezuniyyetden_geri_cagirma' => [
                'label' => 'Məzuniyyətdən geri çağırma',
                'effect' => 'vacation_recall',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək məzuniyyətindən geri çağırılma haqqında',
                    'preamble' => 'İstehsalat zərurətini nəzərə alaraq, [İşçi (yiyəlik)] razılığı ilə, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        'Əmək məzuniyyətində olan [İş yeri] [Vəzifə] [İşçi], [Səbəb], [Geri çağırma tarixi] tarixindən məzuniyyətdən geri çağırılsın.',
                        'Məzuniyyətin istifadə olunmamış hissəsi əməkdaşın arzusu ilə cari iş ili ərzində və ya növbəti iş ilinin məzuniyyətinə birləşdirilməklə verilsin.',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Səbəb' => ['type' => 'text'],
                    'Geri çağırma tarixi' => ['type' => 'date', 'role' => 'recall_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ─────────────── İstifadə olunmamış məzuniyyətə görə kompensasiya ───────────────
            // ƏM m.144.2: paid when the employment contract ends — issued only with a
            // termination order on file, approved only after it. Approval takes the days off
            // the work-year balance (from the named work year, then the oldest); payroll
            // computes the amount.
            'istifade_olunmamis_mezuniyyet_kompensasiyasi' => [
                'label' => 'İstifadə olunmamış məzuniyyətə görə kompensasiya',
                'effect' => 'vacation_compensation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'İstifadə olunmamış əmək məzuniyyətinə görə kompensasiya ödənilməsi haqqında',
                    'preamble' => '[İşçi (yiyəlik)] əmək müqaviləsinə xitam verilməsi ilə əlaqədar, Azərbaycan Respublikası Əmək Məcəlləsinin 144-cü maddəsinin 2-ci hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [İş ili] iş ilindən başlayaraq istifadə olunmamış [Gün sayı] təqvim günü əmək məzuniyyətinə görə pul kompensasiyası ödənilsin.',
                        'Mühasibatlıq kompensasiyanın məbləğini orta əmək haqqı əsasında hesablasın və ödənişi təmin etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'İş ili' => ['type' => 'work_year', 'role' => 'work_year'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────── Qeyri-iş günü işə cəlb etmə ────────────────────────
            // Approval puts the day on record in Attendance as rest-day work.
            'qeyri_is_gunu_ise_celb' => [
                'label' => 'Qeyri-iş günü işə cəlb etmə',
                'effect' => 'non_working_day_work',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'İstirahət (bayram) günü işə cəlb edilmə haqqında',
                    'preamble' => '[Səbəb] ilə əlaqədar, [İşçi (yiyəlik)] razılığı ilə, Azərbaycan Respublikası Əmək Məcəlləsinə uyğun olaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi] [İş günü] tarixində (qeyri-iş günü) işə cəlb edilsin.',
                        'Həmin gün işlədiyinə görə əməkdaşa [Kompensasiya].',
                        'Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Səbəb' => ['type' => 'text'],
                    'İş günü' => ['type' => 'date', 'role' => 'work_date'],
                    'Kompensasiya' => ['type' => 'rest_day_compensation', 'role' => 'compensation'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────────────── Əmrin ləğvi ────────────────────────────────
            // Approval cancels the chosen approved order (its HR effect is reversed);
            // revoking this order re-approves it.
            'emrin_legvi' => [
                'label' => 'Əmrin ləğvi',
                'effect' => 'order_cancellation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmrin ləğv edilməsi haqqında',
                    'preamble' => '[Səbəb] nəzərə alaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi] barəsində verilmiş [Ləğv edilən əmr] əmri ləğv edilsin.',
                        'Ləğv edilən əmrin icrası ilə bağlı aparılmış kadr və mühasibat qeydləri müvafiq qaydada geri qaytarılsın.',
                        'İnsan Resursları və Mühasibatlıq bu əmrdən irəli gələn məsələləri həll etsinlər.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Səbəb' => ['type' => 'text'],
                    'Ləğv edilən əmr' => ['type' => 'approved_order', 'role' => 'target_order'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],
        ];
    }
}
