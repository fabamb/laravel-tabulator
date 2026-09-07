{{-- No ajax-url: pagination/sort/filter run client-side on $rows --}}
<x-tabulator-table
    :data="$rows"
    :columns="[
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>
