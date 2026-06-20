<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 24px; color: #18181b; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        p { color: #52525b; font-size: 13px; }
        .summary { display: flex; gap: 12px; flex-wrap: wrap; margin: 18px 0 24px; }
        .summary-card { border: 1px solid #e4e4e7; border-radius: 12px; padding: 12px 16px; min-width: 180px; }
        .summary-card strong { display: block; font-size: 12px; color: #71717a; text-transform: uppercase; margin-bottom: 6px; }
        .summary-card span { font-size: 18px; font-weight: 700; color: #18181b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e4e4e7; padding: 10px; font-size: 12px; text-align: left; vertical-align: top; }
        th { background: #f4f4f5; text-transform: uppercase; color: #71717a; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $description }}</p>

    @if (! empty($summary))
        <div class="summary">
            @foreach ($summary as $item)
                <div class="summary-card">
                    <strong>{{ $item['label'] }}</strong>
                    <span>{{ $item['value'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        <td>{{ data_get($row, $column['key']) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">{{ __('reports::dashboard.empty.no_report_data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
