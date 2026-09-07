{{-- config('tabulator.locale') defaults to config('app.locale') — set APP_LOCALE=it
     (or config('tabulator.locale', 'it') via published config) and both Tabulator's
     own UI strings and these toolbar tooltips switch to Italian automatically. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :toolbar="[
        'reload' => ['icon' => 'bi bi-arrow-clockwise'],
        'csv' => ['icon' => 'bi bi-filetype-csv'],
    ]"
    :columns="[
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>
