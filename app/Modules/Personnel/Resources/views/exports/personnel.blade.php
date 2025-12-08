<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{__('Person')}}</th>
            <th>{{ __('Teacher') }}</th>
            {{-- <th>{{ __('Remaining visit count') }}</th>
            <th>{{__('Start date') }}</th>
            <th>{{ __('End date') }}</th>
            <th>{{__('Duration')}}</th>
            <th>{{__('Card #')}}</th>
            <th>{{__('Gender')}}</th> --}}
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
                <th>{{ $r[ 'person' ]['gender'] ?? '' ? __('Man') : __('Woman') }}</th> --}}
            </tr>
        @endforeach
    </tbody>
</table>