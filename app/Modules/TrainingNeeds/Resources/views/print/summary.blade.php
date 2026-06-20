<!doctype html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <title>{{ __('training_needs::dashboard.cards.reporting_summary') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; margin: 28px; background: #fafafa; }
        .brand-shell { border: 1px solid #d4d4d8; border-radius: 24px; overflow: hidden; background: #ffffff; }
        .brand-header { padding: 22px 24px; background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 55%, #eff6ff 100%); border-bottom: 1px solid #e4e4e7; }
        .brand-kicker { font-size: 11px; text-transform: uppercase; letter-spacing: .12em; color: #64748b; margin: 0 0 8px; }
        h1,h2,p { margin: 0; }
        h1 { font-size: 24px; margin-bottom: 6px; }
        h2 { font-size: 18px; margin-top: 28px; }
        .brand-meta { font-size: 12px; color: #52525b; }
        .body-pad { padding: 24px; }
        .stats { margin-top: 18px; width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .stats td { padding: 0; }
        .chip { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #ecfdf5; color: #047857; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #ffffff; }
        th, td { border: 1px solid #d4d4d8; padding: 10px 12px; font-size: 12px; text-align: left; vertical-align: top; }
        th { background: #f4f4f5; }
    </style>
</head>
<body>
    <div class="brand-shell">
        <div class="brand-header">
            <p class="brand-kicker">DMX HR • {{ __('training_needs::dashboard.title') }}</p>
            <h1>{{ __('training_needs::dashboard.cards.reporting_summary') }}</h1>
            <p class="brand-meta">{{ now()->format('d.m.Y H:i') }}</p>
        </div>
        <div class="body-pad">
            <table class="stats">
                <tr>
                    <td><span class="chip">{{ __('training_needs::dashboard.cards.delivery_snapshot') }}: {{ count($deliverySummary) }}</span></td>
                    <td><span class="chip">{{ __('training_needs::dashboard.cards.coverage_ratio') }}: {{ count($deliveryPivot) }}</span></td>
                    <td><span class="chip">{{ __('training_needs::dashboard.cards.feedback_session_summary') }}: {{ count($feedbackSummary) }}</span></td>
                </tr>
            </table>

            <h2>{{ __('training_needs::dashboard.cards.delivery_snapshot') }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ __('training_needs::dashboard.fields.session_title') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.program') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.scheduled_start_at') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.status') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.participant_count') }}</th>
                    <th>{{ __('training_needs::dashboard.labels.attended_participants') }}</th>
                    <th>{{ __('training_needs::dashboard.labels.delivery_records') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.average_feedback_score') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($deliverySummary as $row)
                    <tr>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->program_title }}</td>
                        <td>{{ $row->scheduled_start_at ? \Illuminate\Support\Carbon::parse($row->scheduled_start_at)->format('d.m.Y H:i') : '-' }}</td>
                        <td>{{ __('training_needs::dashboard.session_statuses.'.$row->status) }}</td>
                        <td>{{ $row->participant_count }}</td>
                        <td>{{ $row->attended_count }}</td>
                        <td>{{ $row->delivery_records_count }}</td>
                        <td>{{ $row->average_feedback_score }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <h2>{{ __('training_needs::dashboard.cards.coverage_ratio') }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ __('training_needs::dashboard.fields.program') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.delivery_type') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.sessions_count') }}</th>
                    <th>{{ __('training_needs::dashboard.labels.attended_participants') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.delivery_records_count') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.certificates_uploaded') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.average_feedback_score') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($deliveryPivot as $row)
                    <tr>
                        <td>{{ $row->program_title }}</td>
                        <td>{{ __('training_needs::dashboard.delivery_types.'.$row->delivery_type) }}</td>
                        <td>{{ $row->sessions_count }}</td>
                        <td>{{ $row->attended_count }}</td>
                        <td>{{ $row->delivery_records_count }}</td>
                        <td>{{ $row->certificates_uploaded }}</td>
                        <td>{{ $row->average_feedback_score }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <h2>{{ __('training_needs::dashboard.cards.feedback_session_summary') }}</h2>
            <table>
                <thead>
                <tr>
                    <th>{{ __('training_needs::dashboard.fields.session_title') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.forms_count') }}</th>
                    <th>{{ __('training_needs::dashboard.fields.average_feedback_score') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($feedbackSummary as $row)
                    <tr>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->feedback_forms_count ?? 0 }}</td>
                        <td>{{ $row->average_feedback_score ?? 0 }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
