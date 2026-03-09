<table>
    <thead>
    <tr>
        <th>#</th>
        <th>{{ __('vacation::common.labels.structure') }}</th>
        <th>{{ __('personnel::common.labels.position') }}</th>
        <th>{{ __('vacation::common.labels.fullname') }}</th>
        <th>{{ __('vacation::common.labels.location') }}</th>
        <th>{{ __('vacation::common.labels.start_date') }}</th>
        <th>{{ __('vacation::common.labels.end_date') }}</th>
        <th>{{ __('vacation::common.labels.return_work_date') }}</th>
        <th>{{ __('vacation::common.labels.duration') }}</th>
        <th>{{ __('vacation::common.labels.order_hash') }}</th>
        <th>{{ __('vacation::common.labels.given_by') }}</th>
        <th>{{ __('vacation::common.labels.given_date') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach( $report as $r )
        <tr>
            <th>{{ $loop->iteration }}</th>
            <th>{{ $r['personnel']['structure']['name'] ?? '' }} </th>
            <th>{{ $r['personnel']['position']['name'] ?? '' }} </th>
            <th>{{ $r['personnel']['surname'] ?? '' }} {{ $r['personnel']['name'] ?? '' }} {{ $r['personnel']['patronymic'] ?? '' }} </th>
            <th>{{ $r['vacation_places'] }}</th>
            <th>{{ $r['start_date'] }}</th>
            <th>{{ $r['end_date'] }}</th>
            <th>{{ $r['return_work_date'] }}</th>
            <th>{{ $r['duration'] }}</th>
            <th>{{ $r['order_no'] }}</th>
            <th>{{ $r['order_given_by'] }}</th>
            <th>{{ $r['order_date'] }}</th>
        </tr>
    @endforeach
    </tbody>
</table>
