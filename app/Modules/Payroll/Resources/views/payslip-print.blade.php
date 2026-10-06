<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('payroll::dashboard.payslips.detail') }} — {{ $payslip->run?->period?->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, "Segoe UI", Roboto, sans-serif; color: #18181b; margin: 0; padding: 32px; background: #fff; }
        .sheet { max-width: 720px; margin: 0 auto; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #18181b; padding-bottom: 16px; }
        .title { font-size: 22px; font-weight: 700; margin: 0; }
        .muted { color: #71717a; font-size: 12px; }
        .grid { display: flex; gap: 24px; margin-top: 16px; }
        .grid > div { flex: 1; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #71717a; }
        .val { font-size: 14px; font-weight: 600; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #71717a; border-bottom: 1px solid #e4e4e7; padding: 6px 4px; }
        td { font-size: 13px; padding: 7px 4px; border-bottom: 1px solid #f4f4f5; }
        td.amt { text-align: right; font-variant-numeric: tabular-nums; }
        .ded { color: #dc2626; }
        .totals { margin-top: 20px; border-top: 2px solid #18181b; }
        .totals .row { display: flex; justify-content: space-between; padding: 6px 4px; font-size: 14px; }
        .totals .net { font-weight: 700; font-size: 16px; }
        .print-btn { margin: 0 auto 20px; display: block; max-width: 720px; }
        .print-btn button { background: #18181b; color: #fff; border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 600; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="print-btn"><button type="button" onclick="window.print()">{{ __('payroll::dashboard.export.title') }} (PDF)</button></div>
    <div class="sheet">
        <div class="head">
            <div>
                <p class="title">{{ __('payroll::dashboard.title') }}</p>
                <p class="muted">{{ __('payroll::dashboard.payslips.detail') }} · {{ $payslip->run?->period?->code }}</p>
            </div>
            <div style="text-align:right">
                <p class="val">{{ $payslip->personnel?->surname }} {{ $payslip->personnel?->name }}</p>
                <p class="muted">{{ $payslip->tabel_no }}</p>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="label">{{ __('payroll::dashboard.fields.gross') }}</div>
                <div class="val">{{ number_format((float) $payslip->gross, 2) }} {{ $payslip->currency }}</div>
            </div>
            <div>
                <div class="label">{{ __('payroll::dashboard.fields.deductions') }}</div>
                <div class="val">{{ number_format((float) $payslip->total_deductions, 2) }} {{ $payslip->currency }}</div>
            </div>
            <div>
                <div class="label">{{ __('payroll::dashboard.fields.net') }}</div>
                <div class="val">{{ number_format((float) $payslip->net, 2) }} {{ $payslip->currency }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('payroll::dashboard.export.cols.name') }}</th>
                    <th>{{ __('payroll::dashboard.export.cols.kind') }}</th>
                    <th style="text-align:right">{{ __('payroll::dashboard.export.cols.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payslip->lines->where('kind', '!=', 'employer') as $line)
                    <tr>
                        <td>{{ $line->name }}</td>
                        <td>{{ __('payroll::dashboard.kinds.'.$line->kind) }}</td>
                        <td class="amt {{ $line->kind === 'deduction' ? 'ded' : '' }}">{{ $line->kind === 'deduction' ? '−' : '' }}{{ number_format((float) $line->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row net">
                <span>{{ __('payroll::dashboard.fields.net') }}</span>
                <span>{{ number_format((float) $payslip->net, 2) }} {{ $payslip->currency }}</span>
            </div>
        </div>
    </div>
</body>
</html>
