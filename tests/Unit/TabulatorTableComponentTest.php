<?php

namespace Fabamb\LaravelTabulator\Tests\Unit;

use Fabamb\LaravelTabulator\TabulatorTable as TabulatorTableSource;
use Fabamb\LaravelTabulator\View\Components\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;

/**
 * Self-check: the config-assembly logic (rownum/selectable/responsive
 * columns, ajax vs. local mode, per-table overrides) must produce the
 * expected Tabulator config array.
 */
class TabulatorTableComponentTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Fabamb\LaravelTabulator\TabulatorServiceProvider::class];
    }

    public function test_selectable_true_defaults_to_both(): void
    {
        // Bare `selectable` (Blade resolves the valueless attribute to
        // true) behaves like 'both': click-to-select plus the tickbox column.
        $component = new TabulatorTable(selectable: true, rownum: true);

        $this->assertTrue($component->config['selectableRows']);
        $this->assertArrayNotHasKey('rowHeader', $component->config);
        $this->assertSame('rowSelection', $component->config['columns'][0]['formatter']);
        $this->assertSame('rownum', $component->config['columns'][1]['formatter']);
    }

    public function test_selectable_click_string_also_enables_selection(): void
    {
        // match() is strict — 'click' isn't bool true, must be listed
        // explicitly or it falls through to the disabled default.
        $component = new TabulatorTable(selectable: 'click');

        $this->assertTrue($component->config['selectableRows']);
        $this->assertArrayNotHasKey('rowHeader', $component->config);
    }

    public function test_selectable_checkbox_adds_tickbox_column_before_rownum(): void
    {
        $component = new TabulatorTable(selectable: 'checkbox', rownum: true);

        // 'highlight', not true — skips the whole-row click listener so
        // only the tickbox toggles selection, not a click anywhere in the row.
        $this->assertSame('highlight', $component->config['selectableRows']);
        $this->assertArrayNotHasKey('rowHeader', $component->config);
        $this->assertSame('rowSelection', $component->config['columns'][0]['formatter']);
        $this->assertSame('rownum', $component->config['columns'][1]['formatter']);
    }

    public function test_selectable_both_enables_click_and_checkbox_column(): void
    {
        $component = new TabulatorTable(selectable: 'both', rownum: true);

        $this->assertTrue($component->config['selectableRows']);
        $this->assertSame('rowSelection', $component->config['columns'][0]['formatter']);
        $this->assertSame('rownum', $component->config['columns'][1]['formatter']);
    }

    public function test_ajax_url_switches_to_remote_mode(): void
    {
        $component = new TabulatorTable(ajaxUrl: '/data');

        $this->assertSame('/data', $component->config['ajaxURL']);
        $this->assertSame('remote', $component->config['sortMode']);
        $this->assertArrayNotHasKey('data', $component->config);
    }

    public function test_no_ajax_url_uses_local_data(): void
    {
        $component = new TabulatorTable(data: [['id' => 1]]);

        $this->assertSame('local', $component->config['pagination']);
        $this->assertSame([['id' => 1]], $component->config['data']);
    }

    public function test_responsive_layout_defaults_to_config(): void
    {
        $component = new TabulatorTable();

        $this->assertSame(config('tabulator.responsive_layout'), $component->config['responsiveLayout']);
    }

    public function test_responsive_collapse_adds_toggle_column(): void
    {
        $component = new TabulatorTable(options: ['responsiveLayout' => 'collapse']);

        $this->assertArrayNotHasKey('rowHeader', $component->config);
        $this->assertSame('responsiveCollapse', $component->config['columns'][0]['formatter']);
    }

    public function test_responsive_shorthand_sets_fixed_layout_and_collapse(): void
    {
        $component = new TabulatorTable(responsive: true);

        $this->assertSame('fitDataFill', $component->config['layout']);
        $this->assertSame('collapse', $component->config['responsiveLayout']);
    }

    public function test_responsive_shorthand_yields_to_explicit_options(): void
    {
        $component = new TabulatorTable(responsive: true, options: ['layout' => 'fitData', 'responsiveLayout' => 'hide']);

        $this->assertSame('fitData', $component->config['layout']);
        $this->assertSame('hide', $component->config['responsiveLayout']);
    }

    public function test_selectable_and_responsive_collapse_coexist_as_columns(): void
    {
        // Both would compete for Tabulator's single rowHeader slot if
        // implemented that way — as plain columns they don't.
        $component = new TabulatorTable(selectable: 'checkbox', options: ['responsiveLayout' => 'collapse']);

        $this->assertSame('rowSelection', $component->config['columns'][0]['formatter']);
        $this->assertSame('responsiveCollapse', $component->config['columns'][1]['formatter']);
    }

    public function test_actions_prop_appends_formatter_column_at_the_end(): void
    {
        $component = new TabulatorTable(
            columns: [['field' => 'name', 'title' => 'Name']],
            actions: 'userActionsFormatter',
        );

        $this->assertCount(2, $component->config['columns']);
        $lastColumn = $component->config['columns'][1];
        $this->assertSame('userActionsFormatter', $lastColumn['formatter']);
        $this->assertSame(0, $lastColumn['responsive']);
        $this->assertTrue($lastColumn['frozen']);
    }

    public function test_actions_omitted_adds_no_extra_column(): void
    {
        $component = new TabulatorTable(columns: [['field' => 'name', 'title' => 'Name']]);

        $this->assertCount(1, $component->config['columns']);
    }

    public function test_options_override_generated_config(): void
    {
        $component = new TabulatorTable(options: ['layout' => 'fitData']);

        $this->assertSame('fitData', $component->config['layout']);
    }

    public function test_table_columns_used_when_columns_prop_omitted(): void
    {
        $table = new class extends TabulatorTableSource
        {
            public function query(): Builder
            {
                throw new \LogicException('not used in this test');
            }

            public function columns(): array
            {
                return [['field' => 'name', 'title' => 'Name']];
            }
        };

        $component = new TabulatorTable(table: $table);

        $this->assertSame([['field' => 'name', 'title' => 'Name']], $component->config['columns']);
    }

    public function test_explicit_columns_prop_overrides_table_columns(): void
    {
        $table = new class extends TabulatorTableSource
        {
            public function query(): Builder
            {
                throw new \LogicException('not used in this test');
            }

            public function columns(): array
            {
                return [['field' => 'name', 'title' => 'Name']];
            }
        };

        $component = new TabulatorTable(columns: [['field' => 'email', 'title' => 'Email']], table: $table);

        $this->assertSame([['field' => 'email', 'title' => 'Email']], $component->config['columns']);
    }

    public function test_toolbar_defaults_to_empty(): void
    {
        $component = new TabulatorTable();

        $this->assertSame([], $component->toolbar);
    }

    public function test_toolbar_bare_true_uses_default_set(): void
    {
        // Bare `toolbar` (Blade resolves the valueless attribute to true)
        // means: use the standard Toolbar::default() set.
        $bare = new TabulatorTable(toolbar: true);
        $explicit = new TabulatorTable(toolbar: \Fabamb\LaravelTabulator\Toolbar::default());

        $this->assertSame($explicit->toolbar, $bare->toolbar);
    }

    public function test_toolbar_bare_true_with_table_uses_table_toolbar(): void
    {
        $table = new class extends TabulatorTableSource
        {
            public function query(): Builder
            {
                throw new \LogicException('not used in this test');
            }

            public function toolbar(): array
            {
                return ['reload' => ['icon' => 'fas fa-sync']];
            }
        };

        $component = new TabulatorTable(toolbar: true, table: $table);

        $this->assertArrayHasKey('reload', $component->toolbar);
        $this->assertArrayNotHasKey('csv', $component->toolbar);
    }

    public function test_explicit_toolbar_prop_overrides_table_toolbar(): void
    {
        $table = new class extends TabulatorTableSource
        {
            public function query(): Builder
            {
                throw new \LogicException('not used in this test');
            }

            public function toolbar(): array
            {
                return ['reload' => ['icon' => 'fas fa-sync']];
            }
        };

        $component = new TabulatorTable(toolbar: ['csv' => ['icon' => 'fas fa-file-csv']], table: $table);

        $this->assertArrayHasKey('csv', $component->toolbar);
        $this->assertArrayNotHasKey('reload', $component->toolbar);
    }

    public function test_table_with_default_toolbar_hook_used_when_bare_toolbar_true(): void
    {
        // Base TabulatorTable::toolbar() returns Toolbar::default(), so bare
        // `toolbar` gives the same result with or without an (unoverridden) :table.
        $table = new class extends TabulatorTableSource
        {
            public function query(): Builder
            {
                throw new \LogicException('not used in this test');
            }
        };

        $withTable = new TabulatorTable(toolbar: true, table: $table);
        $withoutTable = new TabulatorTable(toolbar: true);

        $this->assertSame($withoutTable->toolbar, $withTable->toolbar);
    }

    public function test_toolbar_keeps_icon_and_explicit_title_no_label(): void
    {
        $toolbar = ['reload' => ['icon' => 'fas fa-sync', 'title' => 'Reload']];

        $component = new TabulatorTable(toolbar: $toolbar);

        $this->assertSame('fas fa-sync', $component->toolbar['reload']['icon']);
        $this->assertSame('Reload', $component->toolbar['reload']['title']);
        $this->assertNull($component->toolbar['reload']['label']);
    }

    public function test_toolbar_label_and_title_both_set_keeps_both(): void
    {
        $toolbar = ['reload' => ['icon' => 'fas fa-sync', 'title' => 'Reload', 'label' => 'Refresh']];

        $component = new TabulatorTable(toolbar: $toolbar);

        $this->assertSame('Refresh', $component->toolbar['reload']['label']);
        $this->assertSame('Reload', $component->toolbar['reload']['title']);
    }

    public function test_toolbar_label_without_title_uses_label_as_tooltip_too(): void
    {
        $toolbar = ['sync' => ['icon' => 'fas fa-sync', 'label' => 'Refresh']];

        $component = new TabulatorTable(toolbar: $toolbar);

        $this->assertSame('Refresh', $component->toolbar['sync']['label']);
        $this->assertSame('Refresh', $component->toolbar['sync']['title']);
    }

    public function test_toolbar_no_label_no_title_falls_back_to_translated_title(): void
    {
        $toolbar = ['reload' => ['icon' => 'fas fa-sync']];

        $component = new TabulatorTable(toolbar: $toolbar);

        $this->assertNull($component->toolbar['reload']['label']);
        $this->assertSame(__('tabulator::tabulator.toolbar.reload'), $component->toolbar['reload']['title']);
    }

    public function test_toolbar_separator_untouched(): void
    {
        $toolbar = ['sep' => ['separator' => true]];

        $component = new TabulatorTable(toolbar: $toolbar);

        $this->assertSame(['separator' => true], $component->toolbar['sep']);
    }

    public function test_locale_pulls_langs_from_translation_files(): void
    {
        config(['tabulator.locale' => 'it']);

        $component = new TabulatorTable();

        $this->assertSame('it', $component->config['locale']);
        $this->assertSame('elemento', $component->config['langs']['it']['groups']['item']);
    }

    public function test_locale_false_skips_langs(): void
    {
        config(['tabulator.locale' => false]);

        $component = new TabulatorTable();

        $this->assertArrayNotHasKey('locale', $component->config);
        $this->assertArrayNotHasKey('langs', $component->config);
    }
}
