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
