/* ==========================================================================
   templates-ui.js — DIR-TEMPLATES-UI-REDESIGN-V1.1
   بحث / فلترة / ترتيب + modal حذف موحّد — Vanilla JS فقط، بلا مكتبات.
   يحافظ على POST /templates/delete باسمَي template_id و _token.
   ========================================================================== */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var page = document.getElementById('tpPage');
        if (!page) { return; }

        var cards = Array.prototype.slice.call(page.querySelectorAll('.tp-card'));
        var originalOrder = cards.slice();
        var searchInput = document.getElementById('tpSearch');
        var statusFilter = document.getElementById('tpStatusFilter');
        var typeFilter = document.getElementById('tpTypeFilter');
        var sortSelect = document.getElementById('tpSort');
        var countLabel = document.getElementById('tpResultCount');
        var noResults = document.getElementById('tpNoResults');

        function normalize(value) {
            return String(value == null ? '' : value).toLowerCase().trim();
        }

        function applyFilters() {
            var term = normalize(searchInput ? searchInput.value : '');
            var status = statusFilter ? statusFilter.value : '';
            var type = typeFilter ? typeFilter.value : '';
            var shown = 0;

            cards.forEach(function (card) {
                var haystack = normalize(card.getAttribute('data-search') + ' ' + card.textContent);
                var ok = (!term || haystack.indexOf(term) !== -1)
                    && (!status || card.getAttribute('data-status') === status)
                    && (!type || card.getAttribute('data-type') === type);
                card.classList.toggle('tp-hidden', !ok);
                if (ok) { shown += 1; }
            });

            if (countLabel) {
                countLabel.textContent = shown + ' من ' + cards.length + ' قالب';
            }
            if (noResults) {
                noResults.classList.toggle('tp-hidden', shown !== 0 || cards.length === 0);
            }
        }

        function applySort() {
            var mode = sortSelect ? sortSelect.value : 'recent';
            var list = cards.slice();
            if (mode === 'name') {
                list.sort(function (a, b) {
                    var an = normalize(a.querySelector('.tp-card__title') ? a.querySelector('.tp-card__title').textContent : '');
                    var bn = normalize(b.querySelector('.tp-card__title') ? b.querySelector('.tp-card__title').textContent : '');
                    return an.localeCompare(bn, 'ar');
                });
            } else if (mode === 'criteria') {
                list.sort(function (a, b) {
                    return (parseInt(b.getAttribute('data-criteria'), 10) || 0) - (parseInt(a.getAttribute('data-criteria'), 10) || 0);
                });
            } else {
                list = originalOrder.slice();
            }
            var grid = document.getElementById('tpGrid');
            if (grid) { list.forEach(function (card) { grid.appendChild(card); }); }
        }

        [searchInput, statusFilter, typeFilter].forEach(function (el) {
            if (el) { el.addEventListener('input', applyFilters); el.addEventListener('change', applyFilters); }
        });
        if (sortSelect) { sortSelect.addEventListener('change', applySort); }

        applyFilters();

        /* ---------- Delete modal (بدل confirm) ---------- */
        var modal = document.getElementById('tpDeleteModal');
        var form = document.getElementById('tpDeleteForm');
        var idField = document.getElementById('tpDeleteId');
        var tokenField = document.getElementById('tpDeleteToken');
        var nameLabel = document.getElementById('tpDeleteName');
        var confirmBtn = document.getElementById('tpDeleteConfirm');
        var cancelBtn = document.getElementById('tpDeleteCancel');
        var lastFocused = null;

        function closeModal() {
            if (!modal) { return; }
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (lastFocused && typeof lastFocused.focus === 'function') { lastFocused.focus(); }
        }

        function openModal(button) {
            if (!modal || !form) { return; }
            lastFocused = button;
            if (idField) { idField.value = button.getAttribute('data-id') || ''; }
            if (nameLabel) { nameLabel.textContent = button.getAttribute('data-name') || ''; }
            if (tokenField) {
                var meta = document.querySelector('meta[name="csrf-token"]');
                tokenField.value = meta ? (meta.content || '') : '';
            }
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            if (cancelBtn && typeof cancelBtn.focus === 'function') { cancelBtn.focus(); }
        }

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest ? event.target.closest('.tp-delete') : null;
            if (trigger) { event.preventDefault(); openModal(trigger); return; }
            if (event.target === modal) { closeModal(); }
        });

        if (cancelBtn) { cancelBtn.addEventListener('click', closeModal); }
        if (confirmBtn && form) { confirmBtn.addEventListener('click', function () { form.submit(); }); }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) { closeModal(); }
        });
    });
})();
