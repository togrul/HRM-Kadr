<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderWordTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Moves standard order types that used to be document-only onto the HR effect they now
 * run: military muster → paid absence, disciplinary sanction → sanction record,
 * substitution → substitution register, salary change → new compensation.
 *
 * Only the stored mapping changes (effect + each variable's effect role); the Word master
 * and its tokens stay as they are, so drafts keep their values. A template is upgraded
 * only while it is still the one the catalogue seeded: effect 'none' and exactly the
 * seeded manual placeholders. A template HR reworked in the designer is reported as
 * edited and left alone — the author can pick the effect there. Idempotent: a template
 * already on the new effect is reported as current.
 */
class StandardOrderEffectUpgrader
{
    /**
     * code => new effect, the seeded manual placeholder labels, and label => role to set.
     *
     * @var array<string,array{effect:string,labels:list<string>,roles:array<string,string>}>
     */
    public const UPGRADES = [
        'herbi_toplanti' => [
            'effect' => 'paid_absence',
            'labels' => ['Hərbi idarə', 'Başlama tarixi', 'Bitmə tarixi', 'Gün sayı', 'Toplantı yeri', 'Əsas mətni'],
            'roles' => ['Başlama tarixi' => 'start_date', 'Bitmə tarixi' => 'end_date', 'Gün sayı' => 'days'],
        ],
        'intizam_tenbehi' => [
            'effect' => 'disciplinary',
            'labels' => ['Pozuntunun təsviri', 'Tənbehin növü', 'Əsas mətni'],
            'roles' => ['Pozuntunun təsviri' => 'violation', 'Tənbehin növü' => 'sanction_type'],
        ],
        'evezetme' => [
            'effect' => 'substitution',
            'labels' => ['Əvəz edilən əməkdaş', 'Başlama tarixi', 'Bitmə tarixi', 'Əvəz edilən vəzifə', 'Əlavə ödəniş', 'Əsas mətni'],
            // The older master states the extra pay as a typed amount, not a percent.
            'roles' => [
                'Əvəz edilən əməkdaş' => 'substituted_employee',
                'Başlama tarixi' => 'start_date',
                'Bitmə tarixi' => 'end_date',
                'Əvəz edilən vəzifə' => 'substituted_position',
                'Əlavə ödəniş' => 'extra_pay_amount',
            ],
        ],
        'emek_haqqi_deyisme' => [
            'effect' => 'salary_change',
            'labels' => ['Dəyişikliyin səbəbi', 'Qüvvəyə minmə tarixi', 'Yeni əmək haqqı', 'Əsas mətni'],
            'roles' => ['Qüvvəyə minmə tarixi' => 'effective_date', 'Yeni əmək haqqı' => 'new_salary'],
        ],
    ];

    /**
     * @return array{updated:list<string>,current:list<string>,edited:list<string>,missing:list<string>}
     */
    public function run(bool $dryRun = false): array
    {
        $result = ['updated' => [], 'current' => [], 'edited' => [], 'missing' => []];

        foreach (self::UPGRADES as $code => $upgrade) {
            $template = OrderWordTemplate::query()->where('code', $code)->first();

            if ($template === null) {
                $result['missing'][] = $code;

                continue;
            }

            if ($template->effect === $upgrade['effect']) {
                $result['current'][] = $code;

                continue;
            }

            $variables = array_values((array) $template->variables);

            if ($template->effect !== 'none' || ! $this->isSeeded($variables, $upgrade)) {
                $result['edited'][] = $code;

                continue;
            }

            if (! $dryRun) {
                DB::transaction(fn () => $template->forceFill([
                    'effect' => $upgrade['effect'],
                    'variables' => $this->withRoles($variables, $upgrade['roles']),
                ])->save());
            }

            $result['updated'][] = $code;
        }

        return $result;
    }

    /**
     * Same manual placeholders as the seed, and none already bound to a different role.
     *
     * @param  list<array<string,mixed>>  $variables
     * @param  array{effect:string,labels:list<string>,roles:array<string,string>}  $upgrade
     */
    private function isSeeded(array $variables, array $upgrade): bool
    {
        $manual = [];
        foreach ($variables as $variable) {
            if (($variable['source'] ?? 'manual') !== 'manual') {
                continue;
            }

            $label = (string) ($variable['label'] ?? '');
            $manual[$label] = true;

            $role = $variable['effect_role'] ?? null;
            if ($role !== null && $role !== ($upgrade['roles'][$label] ?? null)) {
                return false;
            }
        }

        $expected = array_fill_keys($upgrade['labels'], true);
        ksort($manual);
        ksort($expected);

        return array_keys($manual) === array_keys($expected);
    }

    /**
     * @param  list<array<string,mixed>>  $variables
     * @param  array<string,string>  $roles  label => role
     * @return list<array<string,mixed>>
     */
    private function withRoles(array $variables, array $roles): array
    {
        return array_map(function (array $variable) use ($roles): array {
            $label = (string) ($variable['label'] ?? '');

            if (($variable['source'] ?? 'manual') === 'manual' && isset($roles[$label])) {
                $variable['effect_role'] = $roles[$label];
            }

            return $variable;
        }, $variables);
    }
}
