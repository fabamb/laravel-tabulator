{{-- search-value pre-fills the box and applies Tabulator's initialFilter on
     load; component drops the query string via history.replaceState right
     after, so a later reload starts unfiltered. --}}
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
    ]"
    search
    :search-value="request('search')"
/>
