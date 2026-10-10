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
    private const OVERVIEW_MARKDOWN = 'training-performance-user-guide.md';

    /**
     * One module per page: the guide used to stream every module into one endless page,
     * which made the sidebar 456 links long and the page jump on load. Other modules are one
     * click (or one search) away instead.
     */
    public function __invoke(Request $request): View
    {
        $focus = $this->normalizeFocus($request->string('focus')->toString());
        $modules = GuideRegistry::modules();
        $keys = array_keys($modules);
        $position = array_search($focus, $keys, true);
        $headings = $this->headingIndex($modules);
        $markdown = $focus === 'overview' ? self::OVERVIEW_MARKDOWN : $modules[$focus]['markdown'];

        return view('docs.training-performance-guide', [
            'focus' => $focus,
            'focusLabel' => $focus === 'overview' ? 'Ümumi baxış' : $modules[$focus]['label'],
            'modules' => $modules,
            'sidebarGroups' => $this->sidebarGroups($modules, $headings),
            'searchIndex' => $this->searchIndex($modules, $headings),
            'page' => $this->modulePayload($focus),
            'readingMinutes' => max(1, (int) round(count(preg_split('/\\s+/u', (string) file_get_contents(GuideRegistry::markdownPath($markdown)), -1, PREG_SPLIT_NO_EMPTY) ?: []) / 200)),
            'previous' => $position === false ? null : ($keys[$position - 1] ?? 'overview'),
            'next' => $position === false ? ($keys[0] ?? null) : ($keys[$position + 1] ?? null),
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
     * Every H2 (and the H3s under it) of every guide, in document order. The ids match the ones
     * modulePayload() stamps on the rendered headings: {key}-h-N for H2, {key}-s-N for H3.
     *
     * @param  array<string, array<string, mixed>>  $modules
     * @return array<string, list<array{id:string,label:string,level:int,parent:?string}>>
     */
    private function headingIndex(array $modules): array
    {
        $files = ['overview' => self::OVERVIEW_MARKDOWN] + array_map(fn (array $module): string => $module['markdown'], $modules);

        return collect($files)->map(fn (string $file, string $key): array => $this->headings($key, $file))->all();
    }

    /**
     * The guide's own sidebar: the overview, then one group per module whose entries are the
     * module head plus the markdown's H2 headings — so the menu always matches the text.
     *
     * @param  array<string, array<string, mixed>>  $modules
     * @param  array<string, list<array{id:string,label:string,level:int,parent:?string}>>  $headings
     * @return list<array{key:string,label:string,tone:string,items:list<array{id:string,label:string}>}>
     */
    private function sidebarGroups(array $modules, array $headings): array
    {
        $h2 = fn (string $key): array => array_values(array_map(
            fn (array $heading): array => ['id' => $heading['id'], 'label' => $heading['label']],
            array_filter($headings[$key] ?? [], fn (array $heading): bool => $heading['level'] === 2),
        ));

        $groups = [[
            'key' => 'overview',
            'label' => 'Ümumi baxış',
            'tone' => 'zinc',
            'items' => [['id' => 'overview', 'label' => 'Ümumi baxış'], ['id' => 'overview-modules', 'label' => 'Bütün modullar'], ...$h2('overview')],
        ]];

        foreach ($modules as $key => $module) {
            $groups[] = [
                'key' => $key,
                'label' => $module['label'],
                'tone' => $module['tone'],
                'items' => [['id' => $key.'-module', 'label' => 'Modulun məqsədi'], ...$h2($key)],
            ];
        }

        return $groups;
    }

    /**
     * What the header search looks through: module names plus every H2/H3 of every guide,
     * grouped per module as [heading, anchor id, parent H2]. Shipped once as JSON, not markup.
     *
     * @param  array<string, array<string, mixed>>  $modules
     * @param  array<string, list<array{id:string,label:string,level:int,parent:?string}>>  $headings
     * @return list<array{m:string,u:string,h:list<array{0:string,1:string,2:?string}>}>
     */
    private function searchIndex(array $modules, array $headings): array
    {
        $labels = ['overview' => 'Ümumi baxış'] + array_map(fn (array $module): string => $module['label'], $modules);

        return array_map(fn (string $key, string $label): array => [
            'm' => $label,
            'u' => route('docs.guide', $key === 'overview' ? [] : ['focus' => $key], false),
            'h' => array_map(fn (array $heading): array => [$heading['label'], $heading['id'], $heading['parent']], $headings[$key] ?? []),
        ], array_keys($labels), $labels);
    }

    /**
     * @return list<array{id:string,label:string,level:int,parent:?string}>
     */
    private function headings(string $key, string $file): array
    {
        $contents = $this->withoutCodeBlocks(file_get_contents(GuideRegistry::markdownPath($file)) ?: '');
        preg_match_all('/^(#{2,3})[ \t]+(.+?)[ \t#]*$/m', $contents, $matches, PREG_SET_ORDER);

        $counters = [2 => 0, 3 => 0];
        $parent = null;
        $headings = [];

        foreach ($matches as [, $hashes, $text]) {
            $level = strlen($hashes);
            $label = trim(str_replace(['`', '*'], '', $text));
            $counters[$level]++;
            $headings[] = [
                'id' => $key.($level === 2 ? '-h-' : '-s-').$counters[$level],
                'label' => $label,
                'level' => $level,
                'parent' => $level === 3 ? $parent : null,
            ];

            if ($level === 2) {
                $parent = $label;
            }
        }

        return $headings;
    }

    private function withoutCodeBlocks(string $contents): string
    {
        return preg_replace('/^```.*?^```/ms', '', $contents) ?? $contents;
    }

    /**
     * The guide's markdown with ids on its H2/H3s. Its H1 is lifted out to become the page
     * title, so the heading is not printed twice.
     *
     * @return array{key:string,module:?array<string, mixed>,title:?string,html:HtmlString}
     */
    private function modulePayload(string $module): array
    {
        $file = $module === 'overview' ? self::OVERVIEW_MARKDOWN : GuideRegistry::get($module)['markdown'];
        $html = (string) $this->renderMarkdown('docs/scenario/'.$file);
        $title = null;

        if (preg_match('/<h1>(.*?)<\/h1>\s*/s', $html, $match)) {
            $title = strip_tags($match[1]);
            $html = str_replace($match[0], '', $html);
        }

        $counters = ['h2' => 0, 'h3' => 0];
        $html = preg_replace_callback('/<(h2|h3)>/', function (array $match) use ($module, &$counters): string {
            $counters[$match[1]]++;

            return '<'.$match[1].' id="'.$module.($match[1] === 'h2' ? '-h-' : '-s-').$counters[$match[1]].'">';
        }, $html) ?? '';

        return ['key' => $module, 'module' => GuideRegistry::get($module), 'title' => $title, 'html' => new HtmlString($html)];
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
