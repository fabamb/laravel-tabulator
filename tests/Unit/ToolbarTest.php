<?php

namespace Fabamb\LaravelTabulator\Tests\Unit;

use Fabamb\LaravelTabulator\TabulatorServiceProvider;
use Fabamb\LaravelTabulator\Toolbar;
use Orchestra\Testbench\TestCase;

class ToolbarTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [TabulatorServiceProvider::class];
    }

    public function test_default_toolbar_omits_create_without_url(): void
    {
        $this->assertArrayNotHasKey('create', Toolbar::default());
    }

    public function test_default_toolbar_includes_create_with_url_first(): void
    {
        $toolbar = Toolbar::default('/widgets/create');

        $this->assertSame(
            ['create', 'separator', 'reload', 'csv', 'print', 'reset'],
            array_keys($toolbar),
        );
        $this->assertSame('/widgets/create', $toolbar['create']['url']);
    }

    public function test_bulk_buttons_omitted_without_urls(): void
    {
        $toolbar = Toolbar::default();

        $this->assertArrayNotHasKey('bulk-edit', $toolbar);
        $this->assertArrayNotHasKey('bulk-delete', $toolbar);
    }

    public function test_bulk_buttons_included_with_urls(): void
    {
        $toolbar = Toolbar::default(bulkDeleteUrl: '/widgets/bulk-destroy', bulkEditUrl: '/widgets/bulk-edit');

        $this->assertSame(
            ['bulk-edit', 'bulk-delete', 'separator', 'reload', 'csv', 'print', 'reset'],
            array_keys($toolbar),
        );
        $this->assertSame('/widgets/bulk-destroy', $toolbar['bulk-delete']['url']);
        $this->assertSame('/widgets/bulk-edit', $toolbar['bulk-edit']['url']);
    }

    public function test_icons_come_from_config(): void
    {
        $this->assertSame(
            config('tabulator.default_toolbar_icons')['reload']['icon'],
            Toolbar::default()['reload']['icon'],
        );
    }

    public function test_no_separator_without_custom_buttons(): void
    {
        $this->assertArrayNotHasKey('separator', Toolbar::default());
    }

    public function test_no_labels_by_default(): void
    {
        $this->assertArrayNotHasKey('label', Toolbar::default());
    }

    public function test_with_labels_uses_short_label_over_tooltip_text(): void
    {
        $toolbar = Toolbar::default(withLabels: true);

        $this->assertSame(__('tabulator::tabulator.toolbar_label.reload'), $toolbar['reload']['label']);
        $this->assertNotSame(__('tabulator::tabulator.toolbar.reload'), $toolbar['reload']['label']);
    }
}
