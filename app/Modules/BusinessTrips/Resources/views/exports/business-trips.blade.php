<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('business_trips::common.fields.structure') }}</th>
            <th>{{ __('business_trips::common.fields.rank') }}</th>
            <th>{{ __('business_trips::common.fields.fullname') }}</th>
            <th>{{ __('business_trips::common.fields.location') }}</th>
            <th>{{ __('business_trips::common.fields.start_date') }}</th>
            <th>{{ __('business_trips::common.fields.end_date') }}</th>
            <th>{{ __('business_trips::common.fields.order_type') }}</th>
            <th>{{ __('business_trips::common.fields.order_no') }}</th>
            <th>{{ __('business_trips::common.fields.given_by') }}</th>
            <th>{{ __('business_trips::common.fields.given_date') }}</th>
            <th>{{ __('business_trips::common.fields.extra_info') }}</th>
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
                        {{ __('business_trips::common.fields.transportation') }}: {{ __('orders::order_form.transportation.'.($r['attributes']['$transportation']['value'] ?? '')) }}
                        @if(
                            $r['attributes']['$transportation']['value'] == \App\Enums\TransportationEnum::CAR->name
                            && !empty($r['attributes']['$car']['value'])
                        )
                            -  {{ $r['attributes']['$car']['value'] }},
                        @endif,
                    @endif
                    @if(isset($r['attributes']['$weapon']))
                        {{ __('business_trips::common.fields.weapon') }}: {{ $r['attributes']['$weapon']['value'] }},
                    @endif
                    @if(isset($r['attributes']['$service_dog']))
                        {{ __('business_trips::common.fields.service_dog') }}: {{ !empty($r['attributes']['$service_dog']['value']) ? __('business_trips::common.boolean.yes') : __('business_trips::common.boolean.no') }}
                    @endif
            </th>
        </tr>
    @endforeach
    </tbody>
</table>
