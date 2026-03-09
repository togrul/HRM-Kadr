<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('personnel::common.labels.person') }}</th>
            <th>{{ __('personnel::common.labels.teacher') }}</th>
            {{-- Legacy optional columns intentionally omitted. --}}
        </tr>
    </thead>
    <tbody>
    @foreach( $report['data'] as $r )
        <tr>
            <th>{{ $loop->iteration }}</th>
            <th>{{ $r['name'] ?? '' }} {{ $r['surname'] ?? '' }} {{ $r['patronymic'] ?? '' }}</th>
            {{-- Legacy optional row cells intentionally omitted. --}}
        </tr>
    @endforeach
    </tbody>
</table>
