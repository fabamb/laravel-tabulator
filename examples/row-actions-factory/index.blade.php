{{-- Row actions via the reusable factory (resources/js/row-actions.js),
     instead of hand-writing the formatter (see examples/row-actions/ for
     that version). Publish first:
       php artisan vendor:publish --tag=tabulator-js
     then import resources/js/vendor/tabulator/row-actions.js from your own
     resources/js/app.js. --}}
@push('js')
<script>
    const userActions = {
        view:   { url: id => `/users/${id}`,      icon: 'bi-eye' },
        edit:   { url: id => `/users/${id}/edit`, icon: 'bi-pencil' },
        delete: {
            url: id => `/users/${id}`, icon: 'bi-trash', method: 'DELETE',
            class: 'text-danger', label: '{{ __('Delete') }}', confirm: '{{ __('Delete this row?') }}',
        },
    };

    Tabulator.extendModule('format', 'formatters', {
        userActionButtons: Tabulator.rowActionButtons(userActions),
        userActionKebab: Tabulator.rowActionKebab(userActions),
    });
</script>
@endpush

<x-tabulator-table
    id="users-table"
    ajax-url="{{ route('users.data') }}"
    :columns="[
        // Variant 1: inline buttons.
        ['formatter' => 'userActionButtons', 'title' => '', 'width' => 120, 'headerSort' => false, 'hozAlign' => 'center'],

        // Variant 2: kebab + dropdown — pick one, not both, kept side by
        // side here only to show both.
        ['formatter' => 'userActionKebab', 'title' => '', 'width' => 60, 'headerSort' => false, 'hozAlign' => 'center'],

        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
/>
