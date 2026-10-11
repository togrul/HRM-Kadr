<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $scheduleName }}</title>
</head>
<body style="font-family: Arial, 'DejaVu Sans', sans-serif; color: #18181b; font-size: 14px; line-height: 1.5;">
    <p>{{ __('reports::schedules.mail.greeting') }}</p>
    <p>{{ __('reports::schedules.mail.body', ['report' => $reportTitle, 'period' => $periodLabel]) }}</p>
    <p>{{ __('reports::schedules.mail.owner', ['name' => $ownerName]) }}</p>
    <p style="color: #71717a; font-size: 12px;">{{ __('reports::schedules.mail.footer') }}</p>
</body>
</html>
