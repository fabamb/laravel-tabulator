@if ($toolbar || $search)
<div class="d-flex justify-content-between align-items-center mb-2">
    @if ($toolbar)
    <div class="btn-group">
        @foreach ($toolbar as $key => $btn)
        @if (!empty($btn['separator']))
        <span class="{{ config('tabulator.toolbar_separator_class') }}"></span>
        @continue
        @endif
        <button type="button" class="btn {{ config('tabulator.toolbar_button_size_class') }} {{ config('tabulator.toolbar_button_class') }} {{ $btn['class'] ?? '' }}"
                data-tabulator-action="{{ $key }}" data-tabulator-table="{{ $id }}" data-tabulator-url="{{ $btn['url'] ?? '' }}"
                title="{{ $btn['title'] }}">
            <i class="{{ $btn['icon'] }} {{ $btn['icon_class'] ?? '' }}" style="display: inline-block; width: 1em; text-align: center;"></i>
            @if ($btn['label'])
            {{ $btn['label'] }}
            @endif
        </button>
        @endforeach
    </div>
    @endif

    @if ($search)
    <div class="input-group {{ config('tabulator.search_size_class') }} ms-auto" style="max-width: {{ config('tabulator.search_width') }};">
        <span class="input-group-text"><i class="{{ config('tabulator.search_icon') }}"></i></span>
        <input type="search" class="form-control" placeholder="{{ __('tabulator::tabulator.search_placeholder') }}" value="{{ $searchValue }}" data-tabulator-search="{{ $id }}">
    </div>
    @endif
</div>
@endif

<div id="{{ $id }}" {{ $attributes }}></div>

@pushOnce(config('tabulator.stack'), 'tabulator-buttons')
<script>
    // Shared once per page: table instance registry + generic toolbar
    // dispatch, so N tables on the same page register one listener, not one
    // per instance.
    window.tabulatorInstances = window.tabulatorInstances || {};
    const tabulatorNativeActions = {
        reload: (table) => table.setData(),
        csv: (table) => table.download('csv', 'export.csv'),
        print: (table) => table.print(),
        reset: (table) => { table.clearFilter(true); table.clearHeaderFilter(); },
    };
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-tabulator-action]');
        if (!btn) return;

        const table = window.tabulatorInstances[btn.dataset.tabulatorTable];
        if (!table) return;

        const key = btn.dataset.tabulatorAction;
        const action = tabulatorNativeActions[key] ?? window.tabulatorButtons?.[key]?.action;
        action?.(table, table.getSelectedData(), btn.dataset.tabulatorUrl);
    });
</script>
@endPushOnce

@push(config('tabulator.stack'))
<script>
    (function () {
        const table = new Tabulator("#{{ $id }}", @json($config));
        window.tabulatorInstances["{{ $id }}"] = table;

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
