// Reusable, configurable row-action column formatters.
// Not autoloaded — publish with `php artisan vendor:publish --tag=tabulator-js`
// and import from your own resources/js/app.js. See README.md "Row actions".
//
// Usage:
//   const userActions = {
//       view:   { url: id => `/users/${id}`,      icon: 'bi-eye' },
//       edit:   { url: id => `/users/${id}/edit`, icon: 'bi-pencil' },
//       delete: {
//           url: id => `/users/${id}`, icon: 'bi-trash', method: 'DELETE',
//           class: 'text-danger', label: 'Delete', confirm: 'Delete this row?',
//       },
//   };
//   Tabulator.extendModule('format', 'formatters', {
//       userActionButtons: Tabulator.rowActionButtons(userActions),
//       userActionKebab: Tabulator.rowActionKebab(userActions),
//   });
//
// Action config, per key (the key itself is free-form, not assumed):
//   url(id)   required, builds the href / fetch target.
//   icon      required, a Bootstrap Icons class (e.g. 'bi-eye').
//   class     optional, extra CSS class on the button/link.
//   label     optional, title attribute (buttons variant) / menu text (kebab variant).
//             Plain string — caller's own __()/translation output, this file has no i18n.
//   method    optional. Omitted/GET -> plain <a href>. Any other verb -> <button> that
//             fetches with X-CSRF-TOKEN and refreshes the table on success.
//   confirm   optional, confirm() prompt before firing a non-GET action.

function isWriteAction(action) {
    return !!action.method && action.method.toUpperCase() !== 'GET';
}

function buildElement(action, id, table) {
    const el = document.createElement(isWriteAction(action) ? 'button' : 'a');

    if (isWriteAction(action)) {
        el.type = 'button';
        el.addEventListener('click', (e) => {
            e.preventDefault();
            if (action.confirm && !confirm(action.confirm)) return;

            fetch(action.url(id), {
                method: action.method,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            }).then(() => table.setData());
        });
    } else {
        el.href = action.url(id);
    }

    return el;
}

Tabulator.rowActionButtons = function (actions) {
    return function (cell) {
        const id = cell.getData().id;
        const table = cell.getTable();
        const wrap = document.createElement('div');
        wrap.className = 'btn-group';

        Object.values(actions).forEach((action) => {
            const el = buildElement(action, id, table);
            el.className = `btn btn-sm ${action.class || ''}`.trim();
            if (action.label) el.title = action.label;
            el.innerHTML = `<i class="bi ${action.icon}"></i>`;
            wrap.appendChild(el);
        });

        return wrap;
    };
};

Tabulator.rowActionKebab = function (actions) {
    return function (cell) {
        const id = cell.getData().id;
        const table = cell.getTable();

        const wrap = document.createElement('div');
        wrap.className = 'dropdown';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'btn btn-sm bg-body-secondary';
        toggle.setAttribute('data-bs-toggle', 'dropdown');
        toggle.innerHTML = '<i class="bi bi-three-dots-vertical"></i>';

        const menu = document.createElement('ul');
        menu.className = 'dropdown-menu';

        Object.values(actions).forEach((action) => {
            const li = document.createElement('li');
            const el = buildElement(action, id, table);
            el.className = `dropdown-item ${action.class || ''}`.trim();
            el.innerHTML = `<i class="bi ${action.icon}"></i> ${action.label || ''}`;
            li.appendChild(el);
            menu.appendChild(li);
        });

        wrap.appendChild(toggle);
        wrap.appendChild(menu);

        // Tabulator rows are overflow:hidden AND transformed (virtual scroll),
        // trapping a normal Popper dropdown both visually (clipped) and in
        // z-index (stacking context). strategy:'fixed' fixes clipping; moving
        // the menu to <body> only while open fixes stacking, put back on
        // close so redraws don't leak detached nodes.
        toggle.addEventListener('show.bs.dropdown', () => document.body.appendChild(menu));
        toggle.addEventListener('hidden.bs.dropdown', () => wrap.appendChild(menu));
        new bootstrap.Dropdown(toggle, { popperConfig: { strategy: 'fixed' } });

        return wrap;
    };
};
