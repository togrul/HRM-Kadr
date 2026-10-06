@php
    $t = 'performance_evaluation::kpi';
    $p = $t.'.print';
    $fmt = fn ($value, int $decimals = 2) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, $decimals, '.', ' '), '0'), '.');
    $bonus = $card->bonus;
    $person = $card->personnel;
    $manager = $card->manager;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __($p.'.title') }} — {{ $person?->fullname }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', 'DejaVu Sans', Arial, sans-serif; color: #18181b; margin: 0; font-size: 11.5px; line-height: 1.45; background: #f4f4f5; }
        .sheet { max-width: 210mm; margin: 24px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button { font: inherit; font-size: 12.5px; font-weight: 600; padding: 8px 16px; border-radius: 10px; border: 1px solid #e4e4e7; background: #fff; cursor: pointer; }
        .toolbar button.primary { background: #18181b; color: #fff; border-color: #18181b; }
        h1 { font-size: 18px; margin: 0; letter-spacing: -.01em; }
        h2 { font-size: 12.5px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: .06em; color: #52525b; }
        .muted { color: #71717a; }
        .head { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #18181b; padding-bottom: 12px; }
        .meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px 24px; margin-top: 12px; }
        .meta div span { color: #71717a; }
        .scores { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 16px; }
        .score { border: 1px solid #e4e4e7; border-radius: 8px; padding: 8px 10px; }
        .score b { display: block; font-size: 16px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e4e4e7; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { font-size: 10.5px; color: #71717a; font-weight: 600; background: #fafafa; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 40px; }
        .signature { border-top: 1px solid #18181b; padding-top: 6px; }
        .signature .name { font-weight: 600; }
        .signature .line { margin-top: 18px; color: #71717a; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; padding: 0; box-shadow: none; max-width: none; }
            .toolbar { display: none; }
            tr, .score, .signature { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="history.back()">{{ __($p.'.back') }}</button>
        <button type="button" class="primary" onclick="window.print()">{{ __($p.'.print') }}</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                <h1>{{ __($p.'.title') }}</h1>
                <p class="muted">{{ $card->cycle?->name }} · {{ $card->valid_from?->format('d.m.Y') }} – {{ $card->valid_to?->format('d.m.Y') }}</p>
            </div>
            <div style="text-align: right">
                <p><b>{{ __($t.'.card_statuses.'.$card->status) }}</b></p>
                <p class="muted">{{ __($p.'.printed', ['date' => now()->format('d.m.Y H:i')]) }}</p>
            </div>
        </div>

        <div class="meta">
            <div><span>{{ __($p.'.employee') }}:</span> <b>{{ $person?->fullname }}</b> ({{ $person?->tabel_no }})</div>
            <div><span>{{ __($p.'.position') }}:</span> {{ $card->position?->name ?? '—' }}@if ((float) $card->fte < 1) · {{ __($t.'.extra.fte') }} {{ $fmt($card->fte) }}@endif</div>
            <div><span>{{ __($p.'.manager') }}:</span> {{ $manager?->fullname ?? '—' }}</div>
            <div><span>{{ __($t.'.fields.prorata') }}:</span> {{ $fmt((float) $card->prorata_factor * 100) }}%@if ((int) $card->leave_days > 0) · {{ __($t.'.leave.chip', ['days' => $card->leave_days]) }}@endif</div>
        </div>

        <div class="scores">
            <div class="score"><span class="muted">{{ __($t.'.fields.kpi_score') }}</span><b>{{ $card->kpi_score === null ? '—' : $fmt($card->kpi_score).'%' }}</b></div>
            <div class="score"><span class="muted">{{ __($t.'.fields.competency_score') }}</span><b>{{ (float) $card->competency_weight_share > 0 && $card->competency_score !== null ? $fmt($card->competency_score).'%' : '—' }}</b></div>
            <div class="score"><span class="muted">{{ __($t.'.fields.final_score') }}</span><b>{{ $card->final_score === null ? '—' : $fmt($card->final_score).'%' }}</b></div>
            <div class="score"><span class="muted">{{ __($t.'.fields.calibrated_score') }}</span><b>{{ $card->calibrated_score === null ? '—' : $fmt($card->calibrated_score).'%' }}</b></div>
        </div>
        @if ($card->rating_category)
            <p style="margin-top: 8px">{{ __($t.'.fields.rating') }}: <b>{{ __($t.'.ratings.'.$card->rating_category) }}</b></p>
        @endif

        <h2>{{ __($p.'.kpis') }} ({{ $fmt($card->kpi_weight_share) }}%)</h2>
        <table>
            <thead>
                <tr>
                    <th>KPI</th>
                    <th class="num">{{ __($t.'.fields.weight') }}</th>
                    <th class="num">{{ __($t.'.fields.target') }}</th>
                    <th class="num">{{ __($t.'.fields.actual') }}</th>
                    <th class="num">{{ __($p.'.achievement') }}</th>
                    <th class="num">{{ __($p.'.score') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($card->items as $item)
                    <tr>
                        <td><b>{{ $item->kpi?->name }}</b><br><span class="muted">{{ $item->kpi?->code }} · {{ __($t.'.units.'.$item->kpi?->unit) }}</span></td>
                        <td class="num">{{ $fmt($item->weight) }}%</td>
                        <td class="num">{{ $item->kpi?->direction === 'range' ? $fmt($item->range_min).' – '.$fmt($item->range_max) : $fmt($item->target) }}</td>
                        <td class="num">{{ $fmt($item->actual) }}</td>
                        <td class="num">{{ $item->achievement === null ? '—' : $fmt($item->achievement).'%' }}</td>
                        <td class="num"><b>{{ $item->score === null ? '—' : $fmt($item->score).'%' }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($competencies->isNotEmpty())
            <h2>{{ __($p.'.competencies') }} ({{ $fmt($card->competency_weight_share) }}%)</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __($p.'.competency') }}</th>
                        <th class="num">{{ __($p.'.self') }}</th>
                        <th class="num">{{ __($p.'.manager_rating') }}</th>
                        <th>{{ __($p.'.comment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($competencies as $row)
                        <tr>
                            <td>{{ $row['name'] }}<br><span class="muted">{{ $row['section'] }}</span></td>
                            <td class="num">{{ $row['self'] ?? '—' }}</td>
                            <td class="num"><b>{{ $row['manager'] ?? '—' }}</b></td>
                            <td>{{ $row['manager_comment'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($card->calibrations->isNotEmpty())
            <h2>{{ __($p.'.calibration') }}</h2>
            @foreach ($card->calibrations as $calibration)
                <p>{{ __($t.'.calibration_entry', ['delta' => ($calibration->delta > 0 ? '+' : '').$fmt($calibration->delta)]) }} — {{ $calibration->reason }}</p>
            @endforeach
        @endif

        @if ($bonus)
            <h2>{{ __($t.'.bonus.title') }}</h2>
            <p>
                {{ __($t.'.bonus.factors.base_salary') }} {{ $fmt($bonus->base_salary) }} {{ $bonus->currency }} ·
                @if ($bonus->mode === 'order')
                    {{ __($t.'.bonus.factors.reward_months') }} {{ $fmt($bonus->period_months) }} ·
                @else
                    {{ __($t.'.bonus.factors.period_months') }} {{ $fmt($bonus->period_months) }} · {{ __($t.'.bonus.factors.target_pct') }} {{ $fmt($bonus->target_pct) }}% ·
                    {{ __($t.'.bonus.factors.company_mult') }} ×{{ $fmt($bonus->company_mult, 4) }} · {{ __($t.'.bonus.factors.unit_mult') }} ×{{ $fmt($bonus->unit_mult, 4) }} ·
                @endif
                {{ __($t.'.bonus.factors.payout_pct') }} {{ $fmt($bonus->payout_pct) }}% · {{ __($t.'.bonus.factors.prorata') }} {{ $fmt($bonus->prorata * 100) }}%
            </p>
            <p style="font-size: 14px"><b>{{ number_format((float) $bonus->amount, 2, '.', ' ') }} {{ $bonus->currency }}</b> <span class="muted">({{ __($t.'.bonus.statuses.'.$bonus->status) }})</span></p>
        @endif

        <div class="signatures">
            @foreach ([
                'employee' => $person?->fullname,
                'manager' => $manager?->fullname,
                'hr' => null,
            ] as $role => $name)
                <div class="signature">
                    <p class="muted">{{ __($p.'.signatures.'.$role) }}</p>
                    <p class="name">{{ $name ?? '________________________' }}</p>
                    <p class="line">{{ __($p.'.signature_line') }}</p>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
