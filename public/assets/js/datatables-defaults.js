/**
 * Global DataTables defaults.
 *
 * Applies to any table initialised WITHOUT its own `dom` option (e.g. the
 * Racks table). Puts pagination on the left and the "Showing x to y of z"
 * info on the right.
 *
 * Most list pages in this app instead use a custom
 * `dom: 'rt<"d-none datatables-tail"ip>'` + card-footer-slot pattern, which
 * sets its own `dom` per instance and so is NOT affected by this file.
 * Those are instead handled by the `[id$="PaginationSlot"]` / `[id$="InfoSlot"]`
 * CSS order rule in resources/views/layout/app.blade.php instead.
 */
$.extend(true, $.fn.dataTable.defaults, {
    layout: {
        bottomStart: 'paging',
        bottomEnd: 'info'
    }
});
