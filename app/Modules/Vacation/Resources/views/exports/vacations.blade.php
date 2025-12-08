<table>
    <thead>
    <tr>
        <th>#</th>
        <th>{{__('Structure')}}</th>
        <th>{{ __('Position') }}</th>
        <th>{{ __('Fullname') }}</th>
        <th>{{ __('Location') }}</th>
        <th>{{ __('Start date') }}</th>
        <th>{{ __('End date') }}</th>
        <th>{{ __('Return work date') }}</th>
        <th>{{ __('Duration') }}</th>
        <th>{{ __('Order #') }}</th>
        <th>{{ __('Given by') }}</th>
        <th>{{ __('Given date') }}</th>
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
