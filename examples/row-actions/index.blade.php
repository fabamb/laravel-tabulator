{{-- Row actions (View/Edit/Delete), fully client-side: no server
     transformer, formatter reads the row via cell.getData(). See
     formatters.js for the registration these two `formatter` strings
     resolve to. --}}
<x-tabulator-table
    id="users-table"
    ajax-url="{{ route('users.data') }}"
    :columns="[
        // Variant 1: inline buttons.
        ['formatter' => 'rowActionButtons', 'title' => '', 'width' => 100, 'headerSort' => false, 'hozAlign' => 'center'],

        // Variant 2: kebab + dropdown — pick one, not both, kept side by
        // side here only to show both.
        ['formatter' => 'rowActionKebab', 'title' => '', 'width' => 60, 'headerSort' => false, 'hozAlign' => 'center'],

        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>
