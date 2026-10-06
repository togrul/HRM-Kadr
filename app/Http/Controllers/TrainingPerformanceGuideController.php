<?php

namespace App\Http\Controllers;

use App\Support\Docs\GuideRegistry;
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
            'focusLabel' => $focus === 'overview' ? 'Ümumi baxış' : GuideRegistry::get($focus)['label'],
            'modules' => GuideRegistry::modules(),
            'sidebarGroups' => $this->sidebarGroups(),
            'initialModules' => $initialModules,
            'initialModulePayloads' => collect($initialModules)
                ->mapWithKeys(fn (string $module) => [$module => $this->modulePayload($module)])
                ->all(),
        ]);
    }

    public function section(Request $request, string $module): JsonResponse
    {
        abort_unless(GuideRegistry::has($module), 404);

        return response()->json([
            'module' => $module,
            'html' => view('docs.partials.guide-module', $this->modulePayload($module))->render(),
        ]);
    }

    private function normalizeFocus(?string $focus): string
    {
        return GuideRegistry::has((string) $focus) ? (string) $focus : 'overview';
    }

    /**
     * The guide's own sidebar: the overview, then one group per module whose entries are the
     * module head plus the markdown's H2 headings — so the menu always matches the text.
     *
     * @return list<array{key:string,label:string,tone:string,icon:string,items:list<array{id:string,label:string}>}>
     */
    private function sidebarGroups(): array
    {
        $groups = [[
            'key' => 'overview',
            'label' => 'Başlanğıc',
            'tone' => 'zinc',
            'icon' => 'rocket_launch',
            'items' => [
                ['id' => 'overview', 'label' => 'Ümumi baxış'],
                ['id' => 'overview-workflow', 'label' => 'Modulların iş axını'],
            ],
        ]];

        foreach (GuideRegistry::modules() as $key => $module) {
            $items = [['id' => $key.'-module', 'label' => 'Modulun məqsədi']];
            foreach ($this->headings($module['markdown']) as $index => $heading) {
                $items[] = ['id' => $key.'-h-'.($index + 1), 'label' => $heading];
            }

            $groups[] = ['key' => $key] + array_intersect_key($module, array_flip(['label', 'tone', 'icon'])) + ['items' => $items];
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    private function headings(string $file): array
    {
        $contents = $this->withoutCodeBlocks(file_get_contents(GuideRegistry::markdownPath($file)) ?: '');
        preg_match_all('/^##[ \t]+(.+?)[ \t#]*$/m', $contents, $matches);

        return array_map(fn (string $heading): string => trim(str_replace(['`', '*'], '', $heading)), $matches[1]);
    }

    private function withoutCodeBlocks(string $contents): string
    {
        return preg_replace('/^```.*?^```/ms', '', $contents) ?? $contents;
    }

    private function modulePayload(string $module): array
    {
        if ($module === 'overview') {
            return ['overviewHtml' => $this->renderMarkdown('docs/scenario/training-performance-user-guide.md')];
        }

        $entry = GuideRegistry::get($module);
        $index = 0;
        $html = preg_replace_callback('/<h2>/', function () use ($module, &$index): string {
            $index++;

            return '<h2 id="'.$module.'-h-'.$index.'">';
        }, (string) $this->renderMarkdown('docs/scenario/'.$entry['markdown'])) ?? '';

        return ['key' => $module, 'module' => $entry, 'html' => new HtmlString($html)];
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
                'Professional Portfolio',
                'My HR',
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
                'Peşəkar portfel',
                'Şəxsi kabinet',
                'Xülasə',
            ],
            $contents
        );
    }
}
