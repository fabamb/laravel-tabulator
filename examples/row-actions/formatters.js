// resources/js/tabulator-formatters.js, imported by resources/js/app.js,
// loaded before any `new Tabulator(...)` call.
//
// A column's `formatter` prop, as a string, is a lookup against Tabulator's
// formatter registry — not `window[name]` — so it survives the :columns
// array's PHP-array -> @json() -> JS-object trip untouched: it's just a
// string. Custom formatters must be registered here first, same as any
// Tabulator built-in (`plaintext`, `html`, `money`, ...); nothing
// package-specific, no component change needed.
Tabulator.extendModule('format', 'formatters', {
    // Variant 1: inline buttons, one per action.
    rowActionButtons: function (cell) {
        const id = cell.getData().id;
        const wrap = document.createElement('div');
        wrap.className = 'btn-group';
        wrap.innerHTML = `
            <a href="/users/${id}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
            <a href="/users/${id}/edit" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
            <button type="button" class="btn btn-sm btn-danger row-delete" data-id="${id}"><i class="bi bi-trash"></i></button>
        `;
        return wrap;
    },

    // Variant 2: kebab button + Bootstrap 5 dropdown menu.
    rowActionKebab: function (cell) {
        const id = cell.getData().id;
        const wrap = document.createElement('div');
        wrap.className = 'dropdown';
        wrap.innerHTML = `
            <button class="btn btn-sm bg-light" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/users/${id}"><i class="bi bi-eye"></i> View</a></li>
                <li><a class="dropdown-item" href="/users/${id}/edit"><i class="bi bi-pencil"></i> Edit</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item text-danger row-delete" data-id="${id}"><i class="bi bi-trash"></i> Delete</button></li>
            </ul>
        `;

        // Tabulator rows are overflow:hidden AND transformed (virtual
        // scroll), which both clips a normally-positioned Popper dropdown
        // and traps it in that row's own stacking context (z-index alone
        // can't beat a later row's context). strategy: 'fixed' fixes the
        // clipping; moving the menu to <body> only while open fixes the
        // stacking, then it's put back so redraws don't leak detached nodes.
        const toggle = wrap.querySelector('[data-bs-toggle="dropdown"]');
        const menu = wrap.querySelector('.dropdown-menu');
        toggle.addEventListener('show.bs.dropdown', () => document.body.appendChild(menu));
        toggle.addEventListener('hidden.bs.dropdown', () => wrap.appendChild(menu));
        new bootstrap.Dropdown(toggle, { popperConfig: { strategy: 'fixed' } });

        return wrap;
    },
});

// Delegated: buttons are rebuilt on every render/redraw, a direct listener
// on each one would leak.
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.row-delete');
    if (!btn) return;
    if (!confirm('Delete this row?')) return;

    fetch(`/users/${btn.dataset.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    }).then(() => window.tabulatorInstances['users-table'].setData());
});
