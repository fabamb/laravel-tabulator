<?php

namespace Fabamb\LaravelTabulator\View\Components;

use Fabamb\LaravelTabulator\TabulatorTable as TabulatorTableSource;
use Fabamb\LaravelTabulator\Toolbar;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Builds the Tabulator JS config array from component props + package
 * config. Kept as a class component (rather than an anonymous one) because
 * the config-assembly logic (rownum column, selectable rowHeader, ajax vs.
 * local mode, locale, per-table overrides) is non-trivial and worth unit
 * testing without rendering Blade.
 */
class TabulatorTable extends Component
{
    public string $id;

    public array $config;

    public bool $search;

    public ?string $searchValue;

    public array $toolbar;

    public function __construct(
        ?string $id = null,
        array $columns = [],
        ?string $ajaxUrl = null,
        ?array $data = null,
        bool|string $selectable = false,
        bool $rownum = false,
        bool $responsive = false,
        bool $search = false,
        ?string $searchValue = null,
        array $options = [],
        bool|array $toolbar = [],
        ?TabulatorTableSource $table = null,
    ) {
        $this->id = $id ?? 'tabulator-'.uniqid();
        // A non-empty initial value (e.g. from a navbar search redirect)
        // implies the search box, even if the caller didn't pass `search`.
        $this->search = $search || filled($searchValue);
        $this->searchValue = $searchValue;
        $this->toolbar = $this->resolveToolbarButtons($toolbar);
        // Explicit `:columns` always wins; `:table` is just a fallback
        // source for tables that keep a single column definition server-side.
        $columns = $columns ?: ($table?->columns() ?? []);
        $this->config = $this->buildConfig($columns, $ajaxUrl, $data, $selectable, $rownum, $responsive, $options, $searchValue);
    }

    protected function buildConfig(
        array $columns,
        ?string $ajaxUrl,
        ?array $data,
        bool|string $selectable,
        bool $rownum,
        bool $responsive,
        array $options,
        ?string $searchValue = null,
    ): array {
        if ($rownum) {
            array_unshift($columns, $this->rownumColumn());
        }

        // `responsive` is shorthand for the two options responsiveLayout
        // 'collapse' needs together (see README): a fixed-width layout
        // (fitColumns would just shrink columns instead of ever
        // overflowing, so collapse never triggers) plus the option itself.
        // Per-table `:options` still wins over both when set explicitly.
        $layout = $options['layout'] ?? ($responsive ? config('tabulator.responsive_fixed_layout') : config('tabulator.layout'));
        $responsiveLayout = $options['responsiveLayout'] ?? ($responsive ? 'collapse' : config('tabulator.responsive_layout'));

        $config = [
            'layout' => $layout,
            'responsiveLayout' => $responsiveLayout,
            'columns' => $columns,
            'movableColumns' => config('tabulator.movable_columns'),
            'paginationSize' => config('tabulator.pagination_size'),
            'paginationSizeSelector' => config('tabulator.pagination_size_selector'),
            'paginationCounter' => config('tabulator.pagination_counter'),
        ];

        // Both the row-selection checkbox and the responsiveCollapse
        // toggle are usually put in the rowHeader (Tabulator's docs do
        // this), but that's a single slot — they'd fight over it. Both
        // formatters work fine as plain columns too, so that's what these
        // are: independent, freely combinable, no rowHeader involved.
        if ($responsiveLayout === 'collapse') {
            array_unshift($config['columns'], $this->responsiveCollapseColumn());
        }

        // 'selectable' was the option name pre-6.x; Tabulator 6 renamed it
        // to 'selectableRows' (checked against 6.5.0 docs). Its `true`
        // value binds a click listener on the whole row that toggles
        // selection, separate from — and on top of — the tickbox column's
        // own toggle; 'highlight' skips that listener (hover style only),
        // leaving just the tickbox. Bare `selectable` (Blade resolves the
        // valueless attribute to true) means 'both'.
        $selectableRows = match ($selectable) {
            'checkbox' => 'highlight',
            'both', 'click', true => true,
            default => false,
        };

        if ($selectableRows) {
            $config['selectableRows'] = $selectableRows;
        }

        if (in_array($selectable, ['checkbox', 'both', true], strict: true)) {
            array_unshift($config['columns'], $this->selectableColumn());
        }

        if ($locale = config('tabulator.locale')) {
            $config['locale'] = $locale;
            $config['langs'] = [$locale => trans('tabulator::tabulator.js', [], $locale)];
        }

        $config += $ajaxUrl
            ? [
                'ajaxURL' => $ajaxUrl,
                'pagination' => true,
                'paginationMode' => 'remote',
                'sortMode' => 'remote',
                'filterMode' => 'remote',
            ]
            : [
                'pagination' => 'local',
                'data' => $data ?? [],
            ];

        if (filled($searchValue)) {
            $config['initialFilter'] = [['field' => '__global', 'type' => 'like', 'value' => $searchValue]];
        }

        // Per-table override/extension of any Tabulator option above.
        return array_merge($config, $options);
    }

    /**
     * Resolve each button's visible label and tooltip:
     *   label + title  -> label shown, tooltip is title
     *   label, no title -> label shown, tooltip is the label itself
     *   no label, title -> no label, tooltip is title
     *   neither         -> no label, no tooltip
     * A missing 'title' falls back to the translated
     * `tabulator::tabulator.toolbar.<key>` string, when one exists.
     *
     * Bare `toolbar` (Blade resolves the valueless attribute to `true`)
     * means: use the standard Toolbar::default() set, no per-table array.
     */
    protected function resolveToolbarButtons(bool|array $toolbar): array
    {
        if ($toolbar === true) {
            $toolbar = Toolbar::default();
        }

        return collect($toolbar)->map(function (array $btn, string $key) {
            if (! empty($btn['separator'])) {
                return $btn;
            }

            $titleKey = 'tabulator::tabulator.toolbar.'.$key;
            $title = $btn['title'] ?? (\Illuminate\Support\Facades\Lang::has($titleKey) ? __($titleKey) : null);
            $label = $btn['label'] ?? null;

            $btn['label'] = $label;
            $btn['title'] = $label ? ($title ?: $label) : $title;

            return $btn;
        })->all();
    }

    protected function rownumColumn(): array
    {
        return [
            'formatter' => 'rownum',
            'headerSort' => false,
            'resizable' => false,
            'frozen' => true,
            'hozAlign' => 'center',
            'width' => config('tabulator.rownum_width'),
        ];
    }

    protected function selectableColumn(): array
    {
        return [
            'formatter' => 'rowSelection',
            'titleFormatter' => 'rowSelection',
            'headerSort' => false,
            'resizable' => false,
            'frozen' => true,
            'headerHozAlign' => 'center',
            'hozAlign' => 'center',
            'vertAlign' => 'middle',
            'width' => config('tabulator.selectable_width'),
            'widthGrow' => 0,
        ];
    }

    protected function responsiveCollapseColumn(): array
    {
        return [
            'formatter' => 'responsiveCollapse',
            'headerSort' => false,
            'resizable' => false,
            'hozAlign' => 'center',
            'vertAlign' => 'middle',
            // No fixed width: a 30px minWidth clipped the toggle icon,
            // let it size to content instead.
        ];
    }

    public function render(): View
    {
        return view('tabulator::components.tabulator-table');
    }
}
