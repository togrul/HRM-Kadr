<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 14mm; }
        body, h1, p, table, th, td, span, strong { font-family: '{{ $fontFamily }}', sans-serif; }
        body { color: #18181b; font-size: 10pt; }
        h1 { font-size: 16pt; margin: 0 0 4pt 0; }
        p.description { color: #52525b; font-size: 9pt; margin: 0 0 8pt 0; }
        table { width: 100%; border-collapse: collapse; }
        table.meta td { border: none; padding: 1pt 8pt 1pt 0; font-size: 9pt; color: #3f3f46; }
        table.summary td { border: 1px solid #e4e4e7; padding: 4pt 6pt; font-size: 9pt; }
        table.data th, table.data td { border: 1px solid #d4d4d8; padding: 3pt 5pt; font-size: 9pt; text-align: left; vertical-align: top; }
        table.data th { background: #f4f4f5; color: #3f3f46; }
        p.footer { color: #71717a; font-size: 8pt; margin-top: 8pt; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if ($description !== '')
        <p class="description">{{ $description }}</p>
    @endif

    @if (! empty($meta))
        <table class="meta">
            @foreach ($meta as $line)
                <tr>
                    <td><strong>{{ $line['label'] }}:</strong></td>
                    <td>{{ $line['value'] }}</td>
                </tr>
            @endforeach
        </table>
        <br>
    @endif

    @if (! empty($summary))
        <table class="summary">
            <tr>
                @foreach ($summary as $item)
                    <td><strong>{{ $item['label'] }}</strong><br>{{ $item['value'] }}</td>
                @endforeach
            </tr>
        </table>
        <br>
    @endif

    <table class="data">
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
                    <td colspan="{{ max(1, count($columns)) }}">{{ __('reports::dashboard.empty.no_report_data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ __('reports::pdf.generated_at', ['date' => $generatedAt]) }}</p>
</body>
</html>
