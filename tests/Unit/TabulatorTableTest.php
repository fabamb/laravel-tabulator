<?php

namespace Fabamb\LaravelTabulator\Tests\Unit;

use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

/**
 * Self-check: filter + sort + paginate must produce the expected
 * Tabulator JSON shape on a real (in-memory) query.
 */
class TabulatorTableTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
        });

        Widget::insert([
            ['name' => 'Oli Bob', 'age' => 12],
            ['name' => 'Mary May', 'age' => 42],
            ['name' => 'Christine', 'age' => 42],
            ['name' => 'Brendon', 'age' => 16],
        ]);
    }

    public function test_filter_sort_and_paginate(): void
    {
        $table = new WidgetTabulatorTable();

        $request = Request::create('/', 'GET', [
            'filter' => [['field' => 'age', 'type' => '=', 'value' => 42]],
            'sort' => [['field' => 'name', 'dir' => 'asc']],
            'page' => 1,
            'size' => 10,
        ]);

        $response = $table->toResponse($request);
        $payload = $response->getData(true);

        $this->assertSame(1, $payload['last_page']);
        $this->assertCount(2, $payload['data']);
        $this->assertSame('Christine', $payload['data'][0]['name']);
        $this->assertSame('Mary May', $payload['data'][1]['name']);
    }

    public function test_like_filter_and_pagination_size(): void
    {
        $table = new WidgetTabulatorTable();

        $request = Request::create('/', 'GET', [
            'filter' => [['field' => 'name', 'type' => 'like', 'value' => 'o']],
            'page' => 1,
            'size' => 1,
        ]);

        $payload = $table->toResponse($request)->getData(true);

        // "Oli Bob" and "Brendon" both contain "o" -> 2 matches, page size 1 -> 2 pages
        $this->assertSame(2, $payload['last_page']);
        $this->assertCount(1, $payload['data']);
    }

    public function test_all_page_size_returns_every_row_unpaginated(): void
    {
        $table = new WidgetTabulatorTable();

        // Tabulator's "All" page-size option sends size=true, not a number.
        $request = Request::create('/', 'GET', ['size' => 'true']);

        $payload = $table->toResponse($request)->getData(true);

        $this->assertSame(1, $payload['last_page']);
        $this->assertCount(4, $payload['data']);
    }

    public function test_global_search_ors_across_searchable_fields(): void
    {
        $table = new SearchableWidgetTabulatorTable();

        $request = Request::create('/', 'GET', [
            'filter' => [['field' => '__global', 'type' => 'like', 'value' => 'mary']],
        ]);

        $payload = $table->toResponse($request)->getData(true);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('Mary May', $payload['data'][0]['name']);
    }

    public function test_global_search_is_noop_without_searchable_fields(): void
    {
        $table = new WidgetTabulatorTable();

        $request = Request::create('/', 'GET', [
            'filter' => [['field' => '__global', 'type' => 'like', 'value' => 'mary']],
        ]);

        $payload = $table->toResponse($request)->getData(true);

        $this->assertCount(4, $payload['data']);
    }
}

class Widget extends Model
{
    public $timestamps = false;
}

class WidgetTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return Widget::query();
    }
}

class SearchableWidgetTabulatorTable extends WidgetTabulatorTable
{
    protected function searchableFields(): array
    {
        return ['name'];
    }
}
