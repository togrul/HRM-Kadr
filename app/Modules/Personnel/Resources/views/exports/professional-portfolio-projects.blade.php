<table>
    <thead>
        <tr>
            <th>{{ __('personnel::portfolio.fields.project_name') }}</th>
            <th>{{ __('personnel::portfolio.fields.project_code') }}</th>
            <th>{{ __('personnel::portfolio.fields.project_type') }}</th>
            <th>{{ __('personnel::portfolio.fields.role_title') }}</th>
            <th>{{ __('personnel::portfolio.fields.start_date') }}</th>
            <th>{{ __('personnel::portfolio.fields.end_date') }}</th>
            <th>{{ __('personnel::portfolio.fields.sponsor_unit') }}</th>
            <th>{{ __('personnel::portfolio.fields.partner_organizations') }}</th>
            <th>{{ __('personnel::portfolio.fields.status') }}</th>
            <th>{{ __('personnel::portfolio.fields.reference_url') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->project_name }}</td>
                <td>{{ $row->project_code }}</td>
                <td>{{ __('personnel::portfolio.options.project_type.'.$row->project_type) }}</td>
                <td>{{ $row->role_title }}</td>
                <td>{{ optional($row->start_date)->format('d.m.Y') }}</td>
                <td>{{ optional($row->end_date)->format('d.m.Y') }}</td>
                <td>{{ $row->sponsorUnit?->name }}</td>
                <td>{{ $row->partner_organizations }}</td>
                <td>{{ __('personnel::portfolio.status.'.$row->verification_status) }}</td>
                <td>{{ $row->reference_url }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
