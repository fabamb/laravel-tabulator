{{-- $table: passed in from the page route/controller, e.g.
     `return view('users.index', ['table' => $table]);` with
     `App\Tabulator\UserTabulatorTable $table` type-hinted for injection.

     :table pulls columns from UserTabulatorTable::columns() — no :columns
     repeated in every view that renders this table. An explicit :columns
     here would still override it. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :table="$table"
    rownum
    selectable="checkbox"
/>
