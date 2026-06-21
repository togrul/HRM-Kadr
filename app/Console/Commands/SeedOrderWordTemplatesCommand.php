<?php

namespace App\Console\Commands;

use App\Services\Orders\Document\DocxPlaceholderParser;
use App\Services\Orders\Document\OrderTemplateDocxBuilder;
use App\Services\Orders\Document\OrderWordTemplateRepository;
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
 */
class SeedOrderWordTemplatesCommand extends Command
{
    protected $signature = 'orders:seed-word-templates {--only= : Seed only this template code}';

    protected $description = 'Build and register the customer order templates in the Word engine';

    private const ORGANIZATION = '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ';

    private const SIGNATORY = [
        'Baş direktorun İnsan resursları,',
        'təşkilati idarəetmə və',
        'kommunikasiyalar üzrə müavini',
    ];

    private const SIGNATORY_NAME = 'Sübhan İsmayılov';

    /** Automatic variable labels shared across templates: label => employee/system key. */
    private const AUTO = [
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

        foreach ($this->templates() as $code => $template) {
            if ($only && $only !== $code) {
                continue;
            }

            // 1) Build a clean bracketed .docx from the spec.
            $spec = $template['spec'] + [
                'organization' => self::ORGANIZATION,
                'signatory' => self::SIGNATORY,
                'signatory_name' => self::SIGNATORY_NAME,
            ];
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
     * @param  array<string,array{type:string,role?:string}>  $manual  label => field def
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
            'field' => ['key' => $token, 'type' => $def['type']],
            'effect_role' => $def['role'] ?? null,
        ];
    }

    /**
     * The order catalogue. Each entry: human label, approval effect, the document spec
     * (with [bracket] placeholders) and the manual-field definitions for the
     * non-automatic placeholders.
     *
     * @return array<string,array{label:string,effect:string,spec:array<string,mixed>,manual?:array<string,array{type:string,role?:string}>}>
     */
    private function templates(): array
    {
        return [
            // ───────────────────────────── Əmək məzuniyyəti ─────────────────────────────
            'emek_mezuniyyeti' => [
                'label' => 'Əmək məzuniyyəti',
                'effect' => 'vacation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Əmək məzuniyyətinin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 138-ci maddəsinin 2-ci hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] vəzifəsində çalışan [İşçi (yönlük)] [İş ili] iş ilinə görə [Gün sayı] təqvim günü müddətində əmək məzuniyyəti verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Bağırov Səbuhi bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'İş ili' => ['type' => 'work_year'],
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ───────────────────────────── Atalıq məzuniyyəti ───────────────────────────
            'ataliq_mezuniyyeti' => [
                'label' => 'Atalıq məzuniyyəti',
                'effect' => 'vacation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Atalıq məzuniyyətinin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 125-ci maddəsinin 4-cü hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)] [Gün sayı] təqvim günü müddətinə ödənişli atalıq məzuniyyəti verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Gün sayı' => ['type' => 'number', 'role' => 'days'],
                    'Başlama tarixi' => ['type' => 'date', 'role' => 'start_date'],
                    'Bitmə tarixi' => ['type' => 'date', 'role' => 'end_date'],
                    'İşə başlama tarixi' => ['type' => 'date', 'role' => 'return_date'],
                    'Əsas mətni' => ['type' => 'text'],
                ],
            ],

            // ──────────────────────────── Təhsil məzuniyyəti ────────────────────────────
            'tehsil_mezuniyyeti' => [
                'label' => 'Təhsil məzuniyyəti',
                'effect' => 'vacation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Ödənişli təhsil məzuniyyətinin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 124-cü maddəsinin 3-cü hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə], [Təhsil məlumatı] [İşçi (yönlük)] [Gün sayı] təqvim günü müddətində ödənişli təhsil məzuniyyəti verilsin.',
                        'Məzuniyyətin başlanma tarixi [Başlama tarixi], məzuniyyətin bitmə tarixi [Bitmə tarixi], işə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Bağırov Səbuhi bu əmrdən irəli gələn məsələləri həll etsin.',
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
            'odenissiz_mezuniyyet' => [
                'label' => 'Ödənişsiz məzuniyyət',
                'effect' => 'vacation',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Ödənişsiz məzuniyyətin verilməsi haqqında',
                    'preamble' => 'Azərbaycan Respublikası Əmək Məcəlləsinin 129-cu maddəsinin 1-ci hissəsini rəhbər tutaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yönlük)], [Səbəb], [Başlama tarixi]-[Bitmə tarixi] tarixləri ödənişsiz məzuniyyət günləri hesab edilsin.',
                        'İşə başlama tarixi [İşə başlama tarixi] müəyyən edilsin.',
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən irəli gələn məsələləri nəzərə alsın.',
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
            'herbi_toplanti' => [
                'label' => 'Hərbi toplantıda iştirak',
                'effect' => 'none',
                'spec' => [
                    'city' => 'Bakı şəhəri',
                    'subject' => 'Hərbi toplantıda iştirak barədə',
                    'preamble' => 'Səfərbərlik və Hərbi Xidmətə Çağırış üzrə Dövlət Xidmətinin [Hərbi idarə] çağırış vərəqəsini nəzərə alaraq',
                    'clauses' => [
                        '[İş yeri] [Vəzifə] [İşçi (yiyəlik)], Azərbaycan Respublikası Əmək Məcəlləsinin 179-cu maddəsinin 2-ci hissəsinin “g” bəndinə əsasən, orta əmək haqqı ödənilməklə, [Başlama tarixi] tarixindən [Bitmə tarixi] tarixinədək, [Gün sayı] təqvim günü müddətinə, [Toplantı yeri] keçiriləcək hərbi toplantıda iştirakına icazə verilsin.',
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən irəli gələn məsələləri həll etsin.',
                    ],
                    'basis' => '[Əsas mətni]',
                ],
                'manual' => [
                    'Hərbi idarə' => ['type' => 'text'],
                    'Başlama tarixi' => ['type' => 'date'],
                    'Bitmə tarixi' => ['type' => 'date'],
                    'Gün sayı' => ['type' => 'number'],
                    'Toplantı yeri' => ['type' => 'text'],
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
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən irəli gələn məsələləri həll etsin.',
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
                    'preamble' => '“Dinçer və Carçıoğlu” Birgə Müəssisəsinin təsdiq edilmiş yeni təşkilati strukturunu və ştat cədvəlini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 59-cu maddəsinə əsasən',
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
                        'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən irəli gələn məsələləri həll etsin.',
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
                        'Əmrin surəti “Dinçer və Carçıoğlu” Birgə Müəssisəsinin bütün struktur bölmələrinə göndərilsin.',
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
        ];
    }
}
