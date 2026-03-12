<!doctype html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <title>{{ __('performance_evaluation::dashboard.cards.reporting_summary') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; margin: 28px; background: #fafafa; }
        .brand-shell { border: 1px solid #d4d4d8; border-radius: 24px; overflow: hidden; background: #ffffff; }
        .brand-header { padding: 22px 24px; background: linear-gradient(135deg, #eff6ff 0%, #ffffff 55%, #f5f3ff 100%); border-bottom: 1px solid #e4e4e7; }
        .brand-kicker { font-size: 11px; text-transform: uppercase; letter-spacing: .12em; color: #64748b; margin: 0 0 8px; }
        h1,h2,p { margin: 0; }
        h1 { font-size: 24px; margin-bottom: 6px; }
        h2 { font-size: 18px; margin-top: 28px; }
        .brand-meta { font-size: 12px; color: #52525b; }
        .body-pad { padding: 24px; }
        .stats { margin-top: 18px; width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .stats td { padding: 0; }
        .chip { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #eff6ff; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #ffffff; }
        th, td { border: 1px solid #d4d4d8; padding: 10px 12px; font-size: 12px; text-align: left; vertical-align: top; }
        th { background: #f4f4f5; }
    </style>
</head>
<body>
    <div class="brand-shell">
        <div class="brand-header">
            <p class="brand-kicker">DMX HR • Performance Evaluation</p>
            <h1>{{ __('performance_evaluation::dashboard.cards.reporting_summary') }}</h1>
            <p class="brand-meta">{{ now()->format('d.m.Y H:i') }}</p>
        </div>
        <div class="body-pad">
            <table class="stats">
                <tr>
                    <td><span class="chip">{{ __('performance_evaluation::dashboard.cards.recent_forms') }}: {{ count($summary) }}</span></td>
                    <td><span class="chip">{{ __('performance_evaluation::dashboard.cards.weak_links') }}: {{ count($weakPivot) }}</span></td>
                </tr>
            </table>

            <h2>{{ __('performance_evaluation::dashboard.cards.recent_forms') }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ __('performance_evaluation::dashboard.fields.cycle') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.template') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.forms_count') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.average_score') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.high_count') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.medium_count') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.weak_count') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($summary as $row)
                    <tr>
                        <td>{{ $row->cycle_name }}</td>
                        <td>{{ $row->template_name }}</td>
                        <td>{{ $row->forms_count }}</td>
                        <td>{{ $row->average_score }}</td>
                        <td>{{ $row->high_count }}</td>
                        <td>{{ $row->medium_count }}</td>
                        <td>{{ $row->weak_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <h2>{{ __('performance_evaluation::dashboard.cards.weak_links') }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ __('performance_evaluation::dashboard.fields.competency') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.priority') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.status') }}</th>
                    <th>{{ __('performance_evaluation::dashboard.fields.links_count') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($weakPivot as $row)
                    <tr>
                        <td>{{ $row->competency_name }}</td>
                        <td>{{ __('training_needs::dashboard.priorities.'.$row->priority) }}</td>
                        <td>{{ __('training_needs::dashboard.statuses.'.$row->status) }}</td>
                        <td>{{ $row->links_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
