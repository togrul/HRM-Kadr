<?php

namespace App\Modules\Admin\Livewire;

use App\Models\SelfServiceApprovalRoute;
use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Component;

class HrPolicyDiagnostics extends Component
{
    public function render(HrPolicyPackService $service)
    {
        $diagnostics = $service->diagnostics();
        $availablePacks = $service->availablePacks();
        $approvalOverrides = SelfServiceApprovalRoute::query()
            ->latest('id')
            ->get()
            ->unique('request_type')
            ->values();

        return view('admin::livewire.admin.hr-policy-diagnostics', compact('diagnostics', 'availablePacks', 'approvalOverrides'));
    }
}
