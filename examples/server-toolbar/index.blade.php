{{-- $table: passed in from the page route/controller, e.g.
     `return view('users.index', ['table' => $table]);` with
     `App\Tabulator\UserTabulatorTable $table` type-hinted for injection.

     Three ways to define a toolbar for a table that's reused across views: --}}

{{-- 1. Local, view-only — usual :toolbar array, ignores :table entirely. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :toolbar="\Fabamb\LaravelTabulator\Toolbar::default(route('users.create'))"
/>

{{-- 2/3. Server-side — bare `toolbar` pulls from $table->toolbar(), whether
     it extends the default set (case 2, see UserTabulatorTable::toolbar())
     or replaces it entirely (case 3, override without calling parent::toolbar()). --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :table="$table"
    toolbar
/>

{{-- An explicit :toolbar here would still override $table->toolbar(): --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :table="$table"
    :toolbar="['csv' => ['icon' => 'fas fa-file-csv']]"
/>
