<?php

namespace App\Modules\Personnel\Exports;

use App\Models\Personnel;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProfessionalPortfolioAnalyticsExport implements FromView
{
    public function __construct(
        public Personnel $personnel,
        public array $analytics,
    ) {
    }

    public function view(): View
    {
        return view('personnel::exports.professional-portfolio-analytics', [
            'personnel' => $this->personnel,
            'analytics' => $this->analytics,
        ]);
    }
}
