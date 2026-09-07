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
}
