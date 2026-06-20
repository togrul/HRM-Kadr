<table>
    <thead>
        <tr>
            <th>{{ __('personnel::portfolio.analytics.total_records') }}</th>
            <th>{{ __('personnel::portfolio.analytics.speaker_records') }}</th>
            <th>{{ __('personnel::portfolio.analytics.public_mentions') }}</th>
            <th>{{ __('personnel::portfolio.analytics.ongoing_projects') }}</th>
            <th>{{ __('personnel::portfolio.analytics.broken_links') }}</th>
            <th>{{ __('personnel::portfolio.analytics.archive_issues') }}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach ($analytics['cards'] as $card)
                <td>{{ $card['value'] }}</td>
            @endforeach
        </tr>
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th>{{ __('personnel::portfolio.fields.start_date') }}</th>
            <th>{{ __('personnel::portfolio.tabs.events') }}</th>
            <th>{{ __('personnel::portfolio.tabs.media') }}</th>
            <th>{{ __('personnel::portfolio.tabs.projects') }}</th>
            <th>{{ __('personnel::portfolio.analytics.total_records') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($analytics['yearly_activity'] as $row)
            <tr>
                <td>{{ $row['year'] }}</td>
                <td>{{ $row['events'] }}</td>
                <td>{{ $row['media'] }}</td>
                <td>{{ $row['projects'] }}</td>
                <td>{{ $row['total'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th>{{ __('personnel::portfolio.analytics.status_mix') }}</th>
            <th>{{ __('personnel::portfolio.analytics.visibility_mix') }}</th>
            <th>{{ __('personnel::portfolio.analytics.media_health_mix') }}</th>
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < max(count($analytics['status_mix']), count($analytics['visibility_mix']), count($analytics['media_health_mix'])); $i++)
            <tr>
                <td>{{ ($analytics['status_mix'][$i]['label'] ?? '').' '.($analytics['status_mix'][$i]['value'] ?? '') }}</td>
                <td>{{ ($analytics['visibility_mix'][$i]['label'] ?? '').' '.($analytics['visibility_mix'][$i]['value'] ?? '') }}</td>
                <td>{{ ($analytics['media_health_mix'][$i]['label'] ?? '').' '.($analytics['media_health_mix'][$i]['value'] ?? '') }}</td>
            </tr>
        @endfor
    </tbody>
</table>
