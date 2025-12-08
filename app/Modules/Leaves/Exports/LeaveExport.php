<?php

namespace App\Modules\Leaves\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LeaveExport implements FromView
{
    public iterable $rows;

    public function __construct(iterable $rows)
    {
        $this->rows = $rows;
    }

    public function view(): View
    {
        return view('leaves::exports.leaves', ['rows' => $this->rows]);
    }
}
