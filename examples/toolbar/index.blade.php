{{-- Fully custom toolbar --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    selectable
    :toolbar="[
        'reload' => ['icon' => 'fas fa-sync', 'title' => 'Reload'],
        'csv' => ['icon' => 'fas fa-file-csv', 'title' => 'Export CSV'],
        'bulk-delete' => ['icon' => 'fas fa-trash-alt', 'title' => 'Delete', 'url' => route('users.bulk.destroy')],
    ]"
    :columns="[
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>

{{-- Same reload/csv/print/reset set on every table: use the standard set
     instead of retyping it, only the create URL varies per table. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :toolbar="\Fabamb\LaravelTabulator\Toolbar::default(route('users.create'))"
    :columns="[
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>

{{-- Standard set plus bulk actions: pass bulkDeleteUrl/bulkEditUrl too.
     Requires `selectable` — bulk buttons act on `table.getSelectedData()`,
     see window.tabulatorButtons in the README's "Toolbar buttons" section. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    selectable
    :toolbar="\Fabamb\LaravelTabulator\Toolbar::default(
        createUrl: route('users.create'),
        bulkDeleteUrl: route('users.bulk.destroy'),
        bulkEditUrl: route('users.bulk.edit'),
    )"
    :columns="[
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>
