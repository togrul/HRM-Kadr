<?php

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Köçürmənin qüvvəyə minmə tarixi (ƏM m.59: «... tarixdən ... keçirilsin»): köçürmə effekti olan
 * şablonlarda «Köçürmə tarixi» sahəsi `effective_date` roluna bağlanır (başqa sahə həmin rolu
 * daşımırsa). Artıq təsdiqlənmiş köçürmə əmrlərində həmin sahənin dəyəri
 * `effect_state.effective_date`-ə yazılır — vəzifə tarixçəsi o gündən sayılır; sahə boşdursa
 * əmrin tarixi qalır. Təkrar icra təhlükəsizdir.
 */
return new class extends Migration
{
    private const LABEL = 'Köçürmə tarixi';

    private const ROLE = 'effective_date';

    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates')) {
            return;
        }

        $tokens = [];

        foreach (OrderWordTemplate::query()->where('effect', 'transfer')->get() as $template) {
            $variables = array_values((array) $template->variables);
            $bound = collect($variables)->firstWhere('effect_role', self::ROLE);

            if ($bound === null) {
                foreach ($variables as $i => $variable) {
                    if (($variable['source'] ?? 'manual') === 'manual' && ($variable['label'] ?? null) === self::LABEL && empty($variable['effect_role'])) {
                        $variables[$i]['effect_role'] = self::ROLE;
                        $bound = $variables[$i];
                        $template->forceFill(['variables' => $variables])->save();

                        break;
                    }
                }
            }

            if (is_array($bound) && filled($bound['token'] ?? null)) {
                $tokens[(string) $template->code] = (string) $bound['token'];
            }
        }

        if ($tokens === [] || ! Schema::hasTable('order_logs')) {
            return;
        }

        $dates = app(AzerbaijaniDateFormatter::class);

        OrderLog::query()
            ->where('template_snapshot', 'like', '%prev_position_id%')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($tokens, $dates): void {
                foreach ($orders as $order) {
                    $snapshot = (array) $order->template_snapshot;
                    $token = $tokens[(string) ($snapshot['template_code'] ?? '')] ?? null;
                    $state = (array) ($snapshot['effect_state'] ?? []);

                    if ($token === null || ! array_key_exists('prev_position_id', $state) || ! empty($state['effective_date'])) {
                        continue;
                    }

                    $value = data_get($snapshot, 'fields.'.$token);
                    $date = $dates->parse(is_scalar($value) ? (string) $value : null);

                    if ($date === null) {
                        continue;
                    }

                    $state['effective_date'] = $date->format('Y-m-d');
                    $snapshot['effect_state'] = $state;
                    $order->forceFill(['template_snapshot' => $snapshot])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // Rol və tarix təsdiqlənmiş əmrlərin hüquqi faktıdır — geri alınmır.
    }
};
