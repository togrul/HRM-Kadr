<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TrainingPerformanceGuideController extends Controller
{
    public function __invoke(Request $request): View
    {
        $focus = $this->normalizeFocus($request->string('focus')->toString());
        $initialModules = array_values(array_unique(array_filter(['overview', $focus !== 'overview' ? $focus : null])));

        return view('docs.training-performance-guide', [
            'focus' => $focus,
            'initialModules' => $initialModules,
            'initialModulePayloads' => collect($initialModules)
                ->mapWithKeys(fn (string $module) => [$module => $this->modulePayload($module)])
                ->all(),
        ]);
    }

    public function section(Request $request, string $module): JsonResponse
    {
        $module = $this->normalizeFocus($module);

        abort_if($module === 'overview', 404);

        return response()->json([
            'module' => $module,
            'html' => view("docs.partials.guide-{$module}", $this->modulePayload($module))->render(),
        ]);
    }

    private function normalizeFocus(?string $focus): string
    {
        if (! in_array($focus, ['overview', 'training', 'performance', 'attendance', 'orders'], true)) {
            return 'overview';
        }

        return $focus;
    }

    private function modulePayload(string $module): array
    {
        return match ($module) {
            'overview' => [
                'overviewHtml' => $this->renderMarkdown('docs/scenario/training-performance-user-guide.md'),
            ],
            'training' => [
                'trainingHtml' => $this->renderMarkdown('docs/scenario/training-needs-user-guide.md'),
            ],
            'performance' => [
                'performanceHtml' => $this->renderMarkdown('docs/scenario/performance-evaluation-user-guide.md'),
            ],
            'attendance' => [
                'attendanceHtml' => $this->renderMarkdown('docs/scenario/attendance-user-guide.md'),
            ],
            'orders' => [
                'ordersModuleHtml' => $this->renderMarkdown('docs/scenario/orders-module-guide.md'),
                'ordersUserHtml' => $this->renderMarkdown('docs/scenario/orders-user-guide.md'),
                'ordersAdminHtml' => $this->renderMarkdown('docs/scenario/orders-admin-guide.md'),
                'ordersApprovalHtml' => $this->renderMarkdown('docs/scenario/orders-approval-guide.md'),
                'ordersOpsHtml' => $this->renderMarkdown('docs/scenario/orders-ops-commands-guide.md'),
            ],
            default => [],
        };
    }

    private function renderMarkdown(string $relativePath): HtmlString
    {
        $contents = file_get_contents(base_path($relativePath)) ?: '';
        $contents = preg_replace('/\[(.*?)\]\(((?:\/Users\/|\/docs\/)[^)]+)\)/u', '$1', $contents) ?? $contents;
        $contents = preg_replace('/^\s*Bax:\s*\/docs\/[^\n]+$/um', '', $contents) ?? $contents;
        $contents = preg_replace('/^\s*-\s*`[^`]+`\s*$/um', '', $contents) ?? $contents;
        $contents = $this->normalizeVisibleTerms($contents);

        return new HtmlString(
            Str::markdown($contents, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
        );
    }

    private function normalizeVisibleTerms(string $contents): string
    {
        return str_replace(
            [
                'Attendance modulu',
                'Attendance',
                'Orders Module Guide',
                'Orders User Guide',
                'Orders Admin Guide',
                'Orders Approval Guide',
                'Orders Ops / Commands Guide',
                'Orders modulu',
                'Orders',
                'Training Needs modulu',
                'Training Needs',
                'Performance Evaluation modulu',
                'Performance Evaluation',
                'Attendance Operator Guide',
                'Attendance Admin Guide',
                'Attendance Approval Guide',
                'Attendance Ops / Commands Guide',
                'Attendance Permission Matrix',
                'Order registry',
                'Template engine',
                'Admin / template owner',
                'template owner',
                'order status',
                'order type',
                'print payload',
                'order-ləri',
                'order-lərin',
                'order-i',
                'order',
                'template',
                'deleted record-ları',
                'restore edir',
                'DOCX upload edilir',
                'preview edilir',
                'Type seçimi',
                'Type binding',
                'active version',
                'Operator Guide',
                'Admin Guide',
                'Approval Guide',
                'Ops / Commands Guide',
                'Permission Matrix',
                'HR Manager',
                'HR Operator',
                'settings owner',
                'reviewer',
                'Reviewer',
                'manager',
                'Manager',
                'approver',
                'Approver',
                'L&D',
                'User Guide',
                'Admin / Operations guide',
                'Admin / Ops guide',
                'Overview',
            ],
            [
                'Davamiyyət modulu',
                'Davamiyyət',
                'Orders modulu bələdçisi',
                'Orders istifadəçi bələdçisi',
                'Orders admin bələdçisi',
                'Orders təsdiq bələdçisi',
                'Orders əməliyyat / komandalar bələdçisi',
                'Əmrlər modulu',
                'Əmrlər',
                'Təlim ehtiyacı modulu',
                'Təlim ehtiyacı',
                'Performans qiymətləndirməsi modulu',
                'Performans qiymətləndirməsi',
                'Davamiyyət operator bələdçisi',
                'Davamiyyət admin bələdçisi',
                'Davamiyyət təsdiq bələdçisi',
                'Davamiyyət əməliyyat / komandalar bələdçisi',
                'Davamiyyət icazə matrisi',
                'Əmr reyestri',
                'Şablon mühərriki',
                'Admin / şablon sahibi',
                'şablon sahibi',
                'əmr statusu',
                'əmr tipi',
                'çap payload-ı',
                'əmrləri',
                'əmrlərin',
                'əmri',
                'əmr',
                'şablon',
                'silinmiş qeydləri',
                'bərpa edir',
                'DOCX yüklənir',
                'önizlənir',
                'Tip seçimi',
                'Tip bağlama',
                'aktiv versiya',
                'Operator bələdçisi',
                'Admin bələdçisi',
                'Təsdiq bələdçisi',
                'Əməliyyat / komandalar bələdçisi',
                'İcazə matrisi',
                'HR rəhbəri',
                'HR operatoru',
                'tənzimləmə sahibi',
                'yoxlayan',
                'Yoxlayan',
                'rəhbər',
                'Rəhbər',
                'təsdiq verən',
                'Təsdiq verən',
                'təlim və inkişaf',
                'İstifadəçi bələdçisi',
                'Admin / əməliyyat bələdçisi',
                'Admin / əməliyyat bələdçisi',
                'Xülasə',
            ],
            $contents
        );
    }
}
