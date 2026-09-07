<?php

namespace Fabamb\LaravelTabulator\Tests\Unit;

use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;
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

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable();
        });

        $adults = Group::create(['name' => 'Adults']);
        $kids = Group::create(['name' => 'Kids']);

        Widget::where('name', 'Mary May')->update(['group_id' => $adults->id]);
        Widget::where('name', 'Christine')->update(['group_id' => $adults->id]);
        Widget::where('name', 'Oli Bob')->update(['group_id' => $kids->id]);
        Widget::where('name', 'Brendon')->update(['group_id' => $kids->id]);
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

    public function test_added_scope_is_applied_to_query(): void
    {
        $table = new WidgetTabulatorTable();
        $table->addScope(new AgeOver40Scope());

        $payload = $table->toResponse(Request::create('/', 'GET'))->getData(true);

        $this->assertCount(2, $payload['data']);
    }

    public function test_transformer_is_applied_to_paginated_rows(): void
    {
        $table = new UppercaseNameTabulatorTable();

        $payload = $table->toResponse(Request::create('/', 'GET', ['size' => 10]))->getData(true);

        $this->assertSame('OLI BOB', $payload['data'][0]['name']);
    }

    public function test_transformer_is_applied_to_unpaginated_rows(): void
    {
        $table = new UppercaseNameTabulatorTable();

        $payload = $table->toResponse(Request::create('/', 'GET', ['size' => 'true']))->getData(true);

        $this->assertSame('OLI BOB', $payload['data'][0]['name']);
    }

    public function test_dotted_field_filters_on_relation_via_where_has(): void
    {
        $table = new WidgetTabulatorTable();

        $request = Request::create('/', 'GET', [
            'filter' => [['field' => 'group.name', 'type' => '=', 'value' => 'Adults']],
        ]);

        $payload = $table->toResponse($request)->getData(true);

        $this->assertCount(2, $payload['data']);
        $this->assertEqualsCanonicalizing(
            ['Mary May', 'Christine'],
            array_column($payload['data'], 'name'),
        );
    }

    public function test_dotted_field_supports_like_type_on_relation(): void
    {
        $table = new WidgetTabulatorTable();

        $request = Request::create('/', 'GET', [
            'filter' => [['field' => 'group.name', 'type' => 'like', 'value' => 'kid']],
        ]);

        $payload = $table->toResponse($request)->getData(true);

        $this->assertCount(2, $payload['data']);
        $this->assertEqualsCanonicalizing(
            ['Oli Bob', 'Brendon'],
            array_column($payload['data'], 'name'),
        );
    }

}

class AgeOver40Scope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('age', '>', 40);
    }
}

class Widget extends Model
{
    public $timestamps = false;

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}

class Group extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
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

class UppercaseNameTabulatorTable extends WidgetTabulatorTable
{
    protected function transformer(): ?callable
    {
        return fn (Widget $widget) => [
            'id' => $widget->id,
            'name' => strtoupper($widget->name),
            'age' => $widget->age,
        ];
    }
}
