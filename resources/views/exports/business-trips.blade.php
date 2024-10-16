<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{__('Structure')}}</th>
            <th>{{ __('Rank') }}</th>
            <th>{{ __('Fullname') }}</th>
            <th>{{ __('Location') }}</th>
            <th>{{ __('Start date') }}</th>
            <th>{{ __('End date') }}</th>
            <th>{{ __('Order type') }}</th>
            <th>{{ __('Order #') }}</th>
            <th>{{ __('Given by') }}</th>
            <th>{{ __('Given date') }}</th>
            <th>{{ __('Extra info') }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach( $report as $r )
        <tr>
            <th>{{ $loop->iteration }}</th>
            <th>{{ $r['attributes']['$structure']['value'] ?? '' }} </th>
            <th>{{ $r['attributes']['$rank']['value'] ?? '' }} </th>
            <th>{{ $r['attributes']['$fullname']['value'] ?? '' }} </th>
            <th>{{ $r['location'] ?? '' }}</th>
            <th>{{ $r['start_date'] }}</th>
            <th>{{ $r['end_date'] }}</th>
            <th>{{ $r['order']['order_type']['name'] ?? '' }} </th>
            <th>{{ $r['order_no'] }}</th>
            <th>{{ $r['order_given_by'] }}</th>
            <th>{{ $r['order_date'] }}</th>
            <th>
                    @if(isset($r['attributes']['$transportation']))
                        {{ __('Transportation') }}: {{ __($r['attributes']['$transportation']['value']) }}
                        @if(
                            $r['attributes']['$transportation']['value'] == \App\Enums\TransportationEnum::CAR->name
                            && !empty($r['attributes']['$car']['value'])
                        )
                            -  {{ __($r['attributes']['$car']['value']) }},
                        @endif,
                    @endif
                    @if(isset($r['attributes']['$weapon']))
                        {{ __('Weapon') }}: {{ __($r['attributes']['$weapon']['value']) }},
                    @endif
                    @if(isset($r['attributes']['$service_dog']))
                        {{ __('Service dog') }}: {{ __($r['attributes']['$service_dog']['value']) ? 'var' : 'yoxdur' }}
                    @endif
            </th>
        </tr>
    @endforeach
    </tbody>
</table>
