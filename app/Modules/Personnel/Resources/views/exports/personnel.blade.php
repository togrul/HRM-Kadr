<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('personnel::common.labels.person') }}</th>
            <th>{{ __('personnel::common.labels.teacher') }}</th>
            {{-- <th>{{ __('personnel::common.labels.remaining_visit_count') }}</th>
            <th>{{ __('personnel::common.labels.start_date') }}</th>
            <th>{{ __('personnel::common.labels.end_date') }}</th>
            <th>{{ __('personnel::common.labels.duration') }}</th>
            <th>{{ __('personnel::common.labels.card_number') }}</th>
            <th>{{ __('personnel::common.labels.gender') }}</th> --}}
        </tr>
    </thead>
    <tbody>
        @foreach( $report['data'] as $r )
            <tr>
                <th>{{ $loop->iteration }}</th>
                <th>{{ $r['name'] ?? '' }} {{ $r['surname'] ?? '' }} {{ $r['patronymic'] ?? '' }}</th>
                {{-- <th>{{ $r[ 'customer' ]['teacher'][0]['name'] ?? '--' }} {{ $r[ 'customer' ]['teacher'][0]['surname'] ?? '--' }}</th>
                <th>{{ $r['remaining'] ?? ' ' }}</th>
                <th>{{ \Carbon\Carbon::parse($r[ 'seance_start' ] )->format('d.m.Y H:i') }}</th>
                @if(!empty($r['seance_end']))
                <th>{{ \Carbon\Carbon::parse($r[ 'seance_end' ] )->format('d.m.Y H:i') }}</th>
                @else
                <th> - </th>
                @endif
                <th>{{ \Carbon\Carbon::parse($r[ 'seance_end' ])->diffForHumans($r['seance_start'],$options) }}</th>
                <th>{{ $r['assigned_card'] }}</th>
                <th>{{ $r[ 'person' ]['gender'] ?? '' ? __('personnel::common.labels.man') : __('personnel::common.labels.woman') }}</th> --}}
            </tr>
        @endforeach
    </tbody>
</table>
