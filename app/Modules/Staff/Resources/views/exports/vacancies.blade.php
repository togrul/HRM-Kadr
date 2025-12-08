<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{__('Structure')}}</th>
            <th>{{ __('Position') }}</th>
            <th>{{ __('Vacant') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach( $report as $r )
            <tr>
                <th>{{ $loop->iteration }}</th>
                <th>{{ $r['structure']['name'] ?? '' }} </th>
                <th>{{ $r['position']['name'] ?? '' }} </th>
                <th>{{ $r['vacant'] ?? '' }} </th>
            </tr>
        @endforeach
    </tbody>
</table>