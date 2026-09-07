<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[
        ['field' => 'id', 'title' => 'ID', 'width' => 80],
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
        ['field' => 'created_at', 'title' => 'Created at'],
    ]"
    rownum
    selectable
/>
