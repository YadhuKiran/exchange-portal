<?php header('Content-Type: application/javascript'); ?>
(function() {
    'use strict';

    function initTableSearch(tableId, searchInputId) {
        const table = document.getElementById(tableId);
        const searchInput = document.getElementById(searchInputId);
        if (!table || !searchInput) return;

        const rows = table.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = query === '' || text.includes(query) ? '' : 'none';
            });
        });
    }

    function initStatusFilter(tableId, filterSelectId) {
        const table = document.getElementById(tableId);
        const filter = document.getElementById(filterSelectId);
        if (!table || !filter) return;

        const rows = table.querySelectorAll('tbody tr');

        filter.addEventListener('change', function() {
            const value = this.value.toLowerCase();
            rows.forEach(function(row) {
                const statusEl = row.querySelector('.status-badge') || row.querySelector('[data-status]');
                if (!statusEl) { row.style.display = ''; return; }
                const status = (statusEl.getAttribute('data-status') || statusEl.textContent).toLowerCase();
                row.style.display = value === '' || status.includes(value) ? '' : 'none';
            });
        });
    }

    function initPagination(tableId, perPage) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length <= perPage) return;

        const wrapper = table.closest('.table-wrapper') || table.parentElement;
        const paginationEl = document.createElement('div');
        paginationEl.className = 'flex items-center justify-between px-6 py-3 border-t border-slate-200 bg-slate-50/50';

        let currentPage = 1;
        const totalPages = Math.ceil(rows.length / perPage);

        function showPage(page) {
            currentPage = page;
            const start = (page - 1) * perPage;
            const end = start + perPage;
            rows.forEach(function(row, index) {
                row.style.display = index >= start && index < end ? '' : 'none';
            });
            updatePaginationUI();
        }

        function updatePaginationUI() {
            paginationEl.innerHTML =
                '<span class="text-xs text-slate-500">Showing ' + ((currentPage - 1) * perPage + 1) + '–' + Math.min(currentPage * perPage, rows.length) + ' of ' + rows.length + '</span>' +
                '<div class="flex gap-1">' +
                '<button data-page="prev" class="px-2.5 py-1 rounded-lg text-xs font-medium ' + (currentPage <= 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200') + '"' + (currentPage <= 1 ? ' disabled' : '') + '>Prev</button>' +
                '<span class="px-2 py-1 text-xs text-slate-500">' + currentPage + '/' + totalPages + '</span>' +
                '<button data-page="next" class="px-2.5 py-1 rounded-lg text-xs font-medium ' + (currentPage >= totalPages ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200') + '"' + (currentPage >= totalPages ? ' disabled' : '') + '>Next</button>' +
                '</div>';
        }

        paginationEl.addEventListener('click', function(e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            if (btn.dataset.page === 'prev' && currentPage > 1) showPage(currentPage - 1);
            if (btn.dataset.page === 'next' && currentPage < totalPages) showPage(currentPage + 1);
        });

        showPage(1);
        wrapper.appendChild(paginationEl);
    }

    function initSortableTables() {
        document.querySelectorAll('table.sortable').forEach(function(table) {
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(function(header) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const key = this.dataset.sort;
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const ascending = this.dataset.order !== 'asc';

                    headers.forEach(function(h) { h.dataset.order = ''; });
                    this.dataset.order = ascending ? 'asc' : 'desc';

                    rows.sort(function(a, b) {
                        const aVal = a.querySelector('[data-sort-value="' + key + '"]')?.dataset.sortValue || a.cells[Array.from(headers).indexOf(header)]?.textContent.trim() || '';
                        const bVal = b.querySelector('[data-sort-value="' + key + '"]')?.dataset.sortValue || b.cells[Array.from(headers).indexOf(header)]?.textContent.trim() || '';
                        const numA = parseFloat(aVal), numB = parseFloat(bVal);
                        if (!isNaN(numA) && !isNaN(numB)) return ascending ? numA - numB : numB - numA;
                        return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                    });

                    rows.forEach(function(row) { tbody.appendChild(row); });
                });
            });
        });
    }

    function initBulkActions(tableId, checkboxName) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const selectAll = table.querySelector('thead input[type="checkbox"]');
        const checkboxes = table.querySelectorAll('tbody input[name="' + checkboxName + '"]');
        if (!selectAll || !checkboxes.length) return;

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }

    function exportTableToCSV(tableId, filename) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const rows = [];
        table.querySelectorAll('tr').forEach(function(row) {
            const cells = [];
            row.querySelectorAll('th, td').forEach(function(cell, index) {
                if (index === 0 && row.closest('thead')) return;
                cells.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            });
            if (cells.length) rows.push(cells.join(','));
        });
        const blob = new Blob(['\ufeff' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
    }

    window.ExchangeTable = {
        initSearch: initTableSearch,
        initFilter: initStatusFilter,
        initPagination: initPagination,
        initSortable: initSortableTables,
        initBulkActions: initBulkActions,
        exportCSV: exportTableToCSV,
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-table-search]').forEach(function(input) {
            initTableSearch(input.dataset.tableSearch, input.id);
        });
        document.querySelectorAll('[data-table-filter]').forEach(function(select) {
            initStatusFilter(select.dataset.tableFilter, select.id);
        });
        document.querySelectorAll('[data-table-paginate]').forEach(function(table) {
            initPagination(table.id, parseInt(table.dataset.tablePaginate) || 10);
        });
        initSortableTables();
        document.querySelectorAll('[data-bulk-table]').forEach(function(table) {
            initBulkActions(table.id, table.dataset.bulkName || 'selected[]');
        });
    });

})();
