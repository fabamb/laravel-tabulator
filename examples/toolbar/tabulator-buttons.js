// resources/js/tabulator-buttons.js, imported by resources/js/app.js.
// `reload`/`csv`/`print`/`reset` are built into the package — only
// app-specific keys need registering here. The component's shared click
// dispatch (registered once via @pushOnce) looks up any key it doesn't
// recognize as standard in this object.
window.tabulatorButtons = {
    'bulk-delete': {
        action: (table, selected, url) => {
            if (!selected.length) {
                alert('Nothing selected');
                return;
            }

            if (!confirm(`Delete ${selected.length} row(s)?`)) {
                return;
            }

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids: selected.map(row => row.id) }),
            }).then(() => table.setData());
        },
    },
};
