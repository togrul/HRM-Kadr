<table>
    <thead>
        <tr>
            <th>{{ __('personnel::portfolio.fields.title') }}</th>
            <th>{{ __('personnel::portfolio.fields.event_type') }}</th>
            <th>{{ __('personnel::portfolio.fields.participation_role') }}</th>
            <th>{{ __('personnel::portfolio.fields.start_date') }}</th>
            <th>{{ __('personnel::portfolio.fields.end_date') }}</th>
            <th>{{ __('personnel::portfolio.fields.organizer_name') }}</th>
            <th>{{ __('personnel::portfolio.fields.country') }}</th>
            <th>{{ __('personnel::portfolio.fields.visibility') }}</th>
            <th>{{ __('personnel::portfolio.fields.status') }}</th>
            <th>{{ __('personnel::portfolio.fields.source_url') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->title }}</td>
                <td>{{ __('personnel::portfolio.options.event_type.'.$row->event_type) }}</td>
                <td>{{ __('personnel::portfolio.options.participation_role.'.$row->participation_role) }}</td>
                <td>{{ optional($row->start_date)->format('d.m.Y') }}</td>
                <td>{{ optional($row->end_date)->format('d.m.Y') }}</td>
                <td>{{ $row->organizer_name }}</td>
                <td>{{ $row->country?->currentCountryTranslations?->title }}</td>
                <td>{{ __('personnel::portfolio.options.visibility.'.$row->visibility) }}</td>
                <td>{{ __('personnel::portfolio.status.'.$row->verification_status) }}</td>
                <td>{{ $row->source_url }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
