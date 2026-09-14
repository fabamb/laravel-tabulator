<x-tabulator-table
    ajax-url="{{ route('logs.data') }}"
    :columns="[
        ['field' => 'name', 'title' => 'File'],
        ['field' => 'size', 'title' => 'Size'],
    ]"
/>
