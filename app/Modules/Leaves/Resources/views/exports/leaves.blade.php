<table>
    <thead>
    <tr>
        <th>{{ __('Fullname') }}</th>
        <th>{{ __('Position') }}</th>
        <th>{{ __('Structure') }}</th>
        <th>{{ __('Leave type') }}</th>
        <th>{{ __('Start date') }}</th>
        <th>{{ __('End date') }}</th>
        <th>{{ __('Total days') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Reason') }}</th>
        <th>{{ __('Created date') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $leave)
        <tr>
            <td>{{ $leave->personnel?->fullname ?? '' }}</td>
            <td>{{ $leave->personnel?->position_label ?? '' }}</td>
            <td>{{ $leave->personnel?->structure?->name ?? '' }}</td>
            <td>{{ $leave->leaveType?->name ?? '' }}</td>
            <td>{{ optional($leave->starts_at)->format('d.m.Y') }}</td>
            <td>{{ optional($leave->ends_at)->format('d.m.Y') }}</td>
            <td>{{ $leave->total_days ?? '' }}</td>
            <td>{{ $leave->status?->name ?? '' }}</td>
            <td>{{ $leave->reason ?? '' }}</td>
            <td>{{ optional($leave->created_at)->format('d.m.Y H:i') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
