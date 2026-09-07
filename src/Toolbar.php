<?php

namespace Fabamb\LaravelTabulator;

/**
 * Standard toolbar (reload/csv/print/reset, plus optional `create` and
 * bulk-action buttons) for admin panels with many tables that would
 * otherwise repeat the same `:toolbar` array in every view. Icons come from
 * `config('tabulator.default_toolbar_icons')` — override there to switch
 * icon set, no subclassing needed.
 *
 * `bulkEditUrl`/`bulkDeleteUrl` only make sense with `selectable` enabled
 * on the table component — that's the caller's responsibility, not this
 * helper's.
 */
class Toolbar
{
    public static function default(
        ?string $createUrl = null,
        ?string $bulkDeleteUrl = null,
        ?string $bulkEditUrl = null,
    ): array {
        $icons = config('tabulator.default_toolbar_icons');
        $btn = fn (string $key, ?string $url = null) => array_filter([
            'icon' => $icons[$key]['icon'],
            'class' => $icons[$key]['class'] ?? null,
            'icon_class' => $icons[$key]['icon_class'] ?? null,
            'url' => $url,
        ]);

        $custom = array_filter([
            'create' => $createUrl ? $btn('create', $createUrl) : null,
            'bulk-edit' => $bulkEditUrl ? $btn('bulk-edit', $bulkEditUrl) : null,
            'bulk-delete' => $bulkDeleteUrl ? $btn('bulk-delete', $bulkDeleteUrl) : null,
        ]);

        // Visual gap between create/bulk buttons and the standard set,
        // only when there's something on the left to separate from.
        $separator = $custom ? ['separator' => ['separator' => true]] : [];

        return array_merge($custom, $separator, [
            'reload' => $btn('reload'),
            'csv' => $btn('csv'),
            'print' => $btn('print'),
            'reset' => $btn('reset'),
        ]);
    }
}
