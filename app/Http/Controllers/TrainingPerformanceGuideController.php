<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TrainingPerformanceGuideController extends Controller
{
    public function __invoke(Request $request): View
    {
        $focus = $request->string('focus')->toString();

        if (! in_array($focus, ['overview', 'training', 'performance', 'attendance'], true)) {
            $focus = 'overview';
        }

        return view('docs.training-performance-guide', [
            'focus' => $focus,
            'overviewHtml' => $this->renderMarkdown('docs/scenario/training-performance-user-guide.md'),
            'trainingHtml' => $this->renderMarkdown('docs/scenario/training-needs-user-guide.md'),
            'performanceHtml' => $this->renderMarkdown('docs/scenario/performance-evaluation-user-guide.md'),
            'attendanceHtml' => $this->renderMarkdown('docs/scenario/attendance-user-guide.md'),
        ]);
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
                'Training Needs modulu',
                'Training Needs',
                'Performance Evaluation modulu',
                'Performance Evaluation',
                'Attendance Operator Guide',
                'Attendance Admin Guide',
                'Attendance Approval Guide',
                'Attendance Ops / Commands Guide',
                'Attendance Permission Matrix',
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
                'Təlim ehtiyacı modulu',
                'Təlim ehtiyacı',
                'Performans qiymətləndirməsi modulu',
                'Performans qiymətləndirməsi',
                'Davamiyyət operator bələdçisi',
                'Davamiyyət admin bələdçisi',
                'Davamiyyət təsdiq bələdçisi',
                'Davamiyyət əməliyyat / komandalar bələdçisi',
                'Davamiyyət icazə matrisi',
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
