<table>
    <thead>
    <tr>
        <th>{{ __('leaves::common.labels.fullname') }}</th>
        <th>{{ __('leaves::common.labels.position') }}</th>
        <th>{{ __('leaves::common.labels.structure') }}</th>
        <th>{{ __('leaves::common.labels.leave_type') }}</th>
        <th>{{ __('leaves::common.labels.start_date') }}</th>
        <th>{{ __('leaves::common.labels.end_date') }}</th>
        <th>{{ __('leaves::common.labels.total_days') }}</th>
        <th>{{ __('leaves::common.labels.status') }}</th>
        <th>{{ __('leaves::common.labels.reason') }}</th>
        <th>{{ __('leaves::common.labels.created_date') }}</th>
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
