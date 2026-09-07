@if ($search)
<div class="input-group input-group-sm mb-2 ms-auto" style="max-width: 400px;">
    <span class="input-group-text"><i class="{{ config('tabulator.search_icon') }}"></i></span>
    <input type="search" class="form-control" placeholder="{{ __('Search') }}" value="{{ $searchValue }}" data-tabulator-search="{{ $id }}">
</div>
@endif

<div id="{{ $id }}" {{ $attributes }}></div>

@push(config('tabulator.stack'))
<script>
    (function () {
        const table = new Tabulator("#{{ $id }}", @json($config));

        @if ($searchValue)
        // Initial filter came from the query string (e.g. navbar search
        // redirect); drop it once applied so a reload starts unfiltered.
        history.replaceState(null, '', location.pathname);
        @endif

        @if ($search)
        let searchTimer;
        const searchMinChars = {{ config('tabulator.search_min_chars') }};
        document.querySelector('[data-tabulator-search="{{ $id }}"]').addEventListener('input', function (e) {
            clearTimeout(searchTimer);
            const value = e.target.value;

            if (value.length > 0 && value.length < searchMinChars) {
                return;
            }

            searchTimer = setTimeout(() => {
                table.setFilter([{field: '__global', type: 'like', value: value}]);
            }, {{ config('tabulator.search_debounce_ms') }});
        });
        @endif
    })();
</script>
@endpush
