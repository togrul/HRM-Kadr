<?php

namespace App\Modules\Orders\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OrderExport implements FromView
{
    public function __construct(public iterable $report) {}

    public function view(): View
    {
        return view('orders::exports.orders', ['report' => $this->report]);
    }
}
