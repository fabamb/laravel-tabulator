{{-- Column filters are native Tabulator: just set headerFilter, nothing package-specific --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[
        ['field' => 'name', 'title' => 'Name', 'headerFilter' => 'input'],
        ['field' => 'status', 'title' => 'Status', 'headerFilter' => 'list', 'headerFilterParams' => [
            'values' => ['active' => 'Active', 'inactive' => 'Inactive'],
        ]],
    ]"
/>
