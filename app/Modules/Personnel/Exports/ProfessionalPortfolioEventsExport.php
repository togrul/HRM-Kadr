<?php

namespace App\Modules\Personnel\Exports;

use App\Models\Personnel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ProfessionalPortfolioEventsExport implements FromView
{
    public function __construct(
        public Personnel $personnel,
        public Collection $rows,
    ) {
    }

    public function view(): View
    {
        return view('personnel::exports.professional-portfolio-events', [
            'personnel' => $this->personnel,
            'rows' => $this->rows,
        ]);
    }
}
