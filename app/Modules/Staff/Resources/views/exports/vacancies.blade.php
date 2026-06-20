<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('staff::common.fields.structure') }}</th>
            <th>{{ __('staff::common.fields.position') }}</th>
            <th>{{ __('staff::common.fields.vacant') }}</th>
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
