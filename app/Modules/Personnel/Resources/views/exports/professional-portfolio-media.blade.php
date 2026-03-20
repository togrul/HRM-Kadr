<table>
    <thead>
        <tr>
            <th>{{ __('personnel::portfolio.fields.headline') }}</th>
            <th>{{ __('personnel::portfolio.fields.publisher_name') }}</th>
            <th>{{ __('personnel::portfolio.fields.publisher_type') }}</th>
            <th>{{ __('personnel::portfolio.fields.mention_type') }}</th>
            <th>{{ __('personnel::portfolio.fields.published_at') }}</th>
            <th>{{ __('personnel::portfolio.fields.visibility') }}</th>
            <th>{{ __('personnel::portfolio.fields.status') }}</th>
            <th>{{ __('personnel::portfolio.fields.link_health') }}</th>
            <th>{{ __('personnel::portfolio.fields.archive_health') }}</th>
            <th>{{ __('personnel::portfolio.fields.url') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->headline }}</td>
                <td>{{ $row->publisher_name }}</td>
                <td>{{ __('personnel::portfolio.options.publisher_type.'.$row->publisher_type) }}</td>
                <td>{{ __('personnel::portfolio.options.mention_type.'.$row->mention_type) }}</td>
                <td>{{ optional($row->published_at)->format('d.m.Y H:i') }}</td>
                <td>{{ __('personnel::portfolio.options.visibility.'.$row->visibility) }}</td>
                <td>{{ __('personnel::portfolio.status.'.$row->verification_status) }}</td>
                <td>{{ $row->link_check_status ? __('personnel::portfolio.health.link.'.$row->link_check_status) : '' }}</td>
                <td>{{ $row->archive_health_status ? __('personnel::portfolio.health.archive.'.$row->archive_health_status) : '' }}</td>
                <td>{{ $row->url }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
