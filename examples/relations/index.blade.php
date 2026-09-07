{{-- headerFilter on a dotted field filters the relation server-side via whereHas --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[
        ['field' => 'name', 'title' => 'Name', 'headerFilter' => 'input'],
        ['field' => 'email', 'title' => 'Email', 'headerFilter' => 'input'],
        ['field' => 'role.name', 'title' => 'Role', 'headerFilter' => 'input'],
    ]"
/>
