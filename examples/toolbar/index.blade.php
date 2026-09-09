{{-- Fully custom toolbar. Icon-only by default; add 'label' to also show
     text — see README.md "Toolbar buttons" for how label/title combine. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    selectable
    :toolbar="[
        'reload' => ['icon' => 'fas fa-sync', 'label' => 'Reload'],
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

{{-- Same standard set, with each button's translated label shown next to
     its icon instead of icon-only. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :toolbar="\Fabamb\LaravelTabulator\Toolbar::default(route('users.create'), withLabels: true)"
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
