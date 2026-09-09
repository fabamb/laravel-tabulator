{{-- `options` merges last on top of the config the component computed —
     use it for anything Tabulator supports that the component doesn't
     expose as a dedicated prop. (For just responsiveLayout + a matching
     fixed layout, the `responsive` prop is the shortcut — see README.) --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[['field' => 'name', 'title' => 'Name']]"
    :options="[
        'layout' => 'fitDataStretch',
        'paginationSize' => 25,
        'placeholder' => 'No matching rows',
        'responsiveLayout' => 'collapse',
    ]"
/>
