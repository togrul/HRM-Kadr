<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('performance_evaluation::dashboard.labels.test_transcript_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; margin: 32px; font-size: 13px; }
        h1, h2, h3 { margin: 0 0 12px; }
        .meta { margin-bottom: 20px; }
        .meta p { margin: 4px 0; }
        .card { border: 1px solid #d4d4d8; border-radius: 14px; padding: 16px; margin-bottom: 16px; }
        .muted { color: #52525b; font-size: 12px; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #f4f4f5; margin-right: 8px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <h1>{{ __('performance_evaluation::dashboard.labels.test_transcript_title') }}</h1>

    <div class="meta">
        <p><strong>{{ __('performance_evaluation::dashboard.fields.test_bank') }}:</strong> {{ $attempt->session?->bank?->name ?? '—' }}</p>
        <p><strong>{{ __('performance_evaluation::dashboard.fields.personnel') }}:</strong> {{ $attempt->session?->personnel?->fullname ?? '—' }}</p>
        <p><strong>{{ __('performance_evaluation::dashboard.fields.attempt_no') }}:</strong> #{{ $attempt->attempt_no }}</p>
        <p><strong>{{ __('performance_evaluation::dashboard.fields.score') }}:</strong> {{ number_format((float) ($attempt->score ?? 0), 2) }}</p>
        <p><strong>{{ __('performance_evaluation::dashboard.fields.percentage') }}:</strong> {{ number_format((float) ($attempt->percentage ?? 0), 2) }}%</p>
        <p><strong>{{ __('performance_evaluation::dashboard.fields.submitted_at') }}:</strong> {{ $attempt->submitted_at?->format('d.m.Y H:i') ?? '—' }}</p>
    </div>

    <h2>{{ __('performance_evaluation::dashboard.labels.question_breakdown_title') }}</h2>
    @foreach ($analytics['question_rows'] as $row)
        <div class="card">
            <h3>{{ $row['index'] }}. {{ $row['prompt'] }}</h3>
            <p class="muted">{{ __('performance_evaluation::dashboard.question_types.'.$row['question_type']) }}</p>
            <p><strong>{{ __('performance_evaluation::dashboard.fields.answer_text') }}:</strong> {{ $row['answer_text'] }}</p>
            @if ($row['correct_answer'])
                <p><strong>{{ __('performance_evaluation::dashboard.labels.correct_answer') }}:</strong> {{ $row['correct_answer'] }}</p>
            @endif
            <p>
                <span class="pill">{{ __('performance_evaluation::dashboard.fields.review_status') }}: {{ __('performance_evaluation::dashboard.review_statuses.'.$row['review_status']) }}</span>
                <span class="pill">{{ __('performance_evaluation::dashboard.fields.final_score') }}: {{ number_format((float) ($row['final_score'] ?? 0), 2) }}/{{ number_format((float) ($row['max_score'] ?? 0), 2) }}</span>
                @if ($row['is_correct'] !== null)
                    <span class="pill">{{ __('performance_evaluation::dashboard.fields.is_correct') }}: {{ $row['is_correct'] ? __('performance_evaluation::dashboard.labels.answer_correct') : __('performance_evaluation::dashboard.labels.answer_incorrect') }}</span>
                @endif
            </p>
            @if ($row['feedback'])
                <p><strong>{{ __('performance_evaluation::dashboard.fields.feedback') }}:</strong> {{ $row['feedback'] }}</p>
            @endif
        </div>
    @endforeach

    <h2>{{ __('performance_evaluation::dashboard.labels.review_timeline_title') }}</h2>
    @foreach ($analytics['timeline'] as $event)
        <div class="card">
            <p><strong>{{ $event['title'] }}</strong></p>
            <p class="muted">{{ optional($event['meta'])->format('d.m.Y H:i') ?? '—' }}</p>
            @if ($event['description'])
                <p>{{ $event['description'] }}</p>
            @endif
        </div>
    @endforeach
</body>
</html>
