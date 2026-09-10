// Reusable toolbar bulk-action handlers, for window.tabulatorButtons entries
// that act on the current selection (confirm-then-fetch, prompt-then-fetch).
// Not autoloaded — publish with `php artisan vendor:publish --tag=tabulator-js`
// and load from your own JS. See README.md "Toolbar buttons".
//
// Usage:
//   window.tabulatorButtons = {
//       'bulk-delete': Tabulator.bulkAction({ method: 'DELETE', confirm: 'Delete selected rows?' }),
//       'bulk-tags': Tabulator.bulkAction({
//           method: 'PUT', prompt: 'Tags, comma separated:', bodyKey: 'tags',
//       }),
//   };
//
// Options:
//   method    required, HTTP verb for the fetch.
//   confirm   optional, confirm() text shown before firing.
//   prompt    optional, prompt() text; the entered value is sent as
//             { [bodyKey]: value }. Cancelling the prompt aborts the action.
//   bodyKey   optional, defaults to 'value'. Only used with `prompt`.
//   empty     optional, alert() text shown when nothing is selected.

Tabulator.bulkAction = function (options) {
    return {
        action: (table, selected, url) => {
            if (!selected.length) {
                alert(options.empty || 'Select at least one row.');
                return;
            }

            const body = { ids: selected.map((row) => row.id) };

            if (options.prompt) {
                const value = prompt(options.prompt);
                if (value === null) return;
                body[options.bodyKey || 'value'] = value;
            }

            if (options.confirm && !confirm(options.confirm)) return;

            fetch(url, {
                method: options.method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            })
                .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message);
                        return;
                    }
                    table.setData();
                });
        },
    };
};
