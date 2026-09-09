<?php

namespace Fabamb\LaravelTabulator\Tests\Unit;

use Fabamb\LaravelTabulator\View\Components\TabulatorTable;
use Orchestra\Testbench\TestCase;

/**
 * Self-check: the config-assembly logic (rownum column, selectable
 * rowHeader, ajax vs. local mode, per-table overrides) must produce the
 * expected Tabulator config array.
 */
class TabulatorTableComponentTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Fabamb\LaravelTabulator\TabulatorServiceProvider::class];
    }

    public function test_selectable_adds_row_header_without_touching_rownum(): void
    {
        $component = new TabulatorTable(selectable: true, rownum: true);

        $this->assertTrue($component->config['selectable']);
        $this->assertSame('rowSelection', $component->config['rowHeader']['formatter']);
        $this->assertSame('rownum', $component->config['columns'][0]['formatter']);
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

    public function test_options_override_generated_config(): void
    {
        $component = new TabulatorTable(options: ['layout' => 'fitData']);

        $this->assertSame('fitData', $component->config['layout']);
    }

    public function test_toolbar_defaults_to_empty(): void
    {
        $component = new TabulatorTable();

        $this->assertSame([], $component->toolbar);
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
