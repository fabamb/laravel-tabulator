<?php

namespace Fabamb\LaravelTabulator\View\Components;

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
        bool $selectable = false,
        bool $rownum = false,
        bool $search = false,
        ?string $searchValue = null,
        array $options = [],
        array $toolbar = [],
    ) {
        $this->id = $id ?? 'tabulator-'.uniqid();
        // A non-empty initial value (e.g. from a navbar search redirect)
        // implies the search box, even if the caller didn't pass `search`.
        $this->search = $search || filled($searchValue);
        $this->searchValue = $searchValue;
        $this->toolbar = $this->resolveToolbarButtons($toolbar);
        $this->config = $this->buildConfig($columns, $ajaxUrl, $data, $selectable, $rownum, $options, $searchValue);
    }

    protected function buildConfig(
        array $columns,
        ?string $ajaxUrl,
        ?array $data,
        bool $selectable,
        bool $rownum,
        array $options,
        ?string $searchValue = null,
    ): array {
        if ($rownum) {
            array_unshift($columns, $this->rownumColumn());
        }

        $config = [
            'layout' => config('tabulator.layout'),
            'columns' => $columns,
            'movableColumns' => config('tabulator.movable_columns'),
            'paginationSize' => config('tabulator.pagination_size'),
            'paginationSizeSelector' => config('tabulator.pagination_size_selector'),
            'paginationCounter' => config('tabulator.pagination_counter'),
        ];

        if ($selectable) {
            $config['selectable'] = true;
            $config['rowHeader'] = $this->selectableRowHeader();
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
     */
    protected function resolveToolbarButtons(array $toolbar): array
    {
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

    protected function selectableRowHeader(): array
    {
        return [
            'formatter' => 'rowSelection',
            'titleFormatter' => 'rowSelection',
            'headerSort' => false,
            'resizable' => false,
            'frozen' => true,
            'headerHozAlign' => 'center',
            'hozAlign' => 'center',
            'width' => config('tabulator.selectable_width'),
            'widthGrow' => 0,
        ];
    }

    public function render(): View
    {
        return view('tabulator::components.tabulator-table');
    }
}
