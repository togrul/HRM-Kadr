<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('orders::order_list.table.order_no') }}</th>
            <th>{{ __('orders::order_list.table.type') }}</th>
            <th>{{ __('orders::order_list.table.given_date') }}</th>
            <th>{{ __('orders::order_list.table.given_by') }}</th>
            <th>{{ __('orders::order_list.table.status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report as $order)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $order->order_no }}</td>
                <td>{{ $order->order?->name ?? (data_get($order->template_snapshot, 'label') ?? '') }}</td>
                <td>{{ \Carbon\Carbon::parse($order->given_date)->format('d.m.Y') }}</td>
                <td>{{ trim($order->given_by.' '.$order->given_by_rank) }}</td>
                <td>{{ $order->status?->name }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
