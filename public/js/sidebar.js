/* =====================================================================
   CD-012 — سلوك الشريط الجانبي
   - فتح/إغلاق الدرج على الجوال + الخلفية المعتمة.
   - فتح مجموعات القائمة الفرعية (أكورديون).
   - إغلاق الشريط عند النقر خارج الروابط على الجوال.
   ===================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;
        var toggle = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');
        var sidebar = document.getElementById('appSidebar');

        function closeSidebar() {
            body.classList.remove('sidebar-open');
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                body.classList.toggle('sidebar-open');
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });

        // أكورديون المجموعات
        var groupToggles = document.querySelectorAll('.sidebar-group-toggle');
        Array.prototype.forEach.call(groupToggles, function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.sidebar-group');
                if (!group) { return; }
                var isOpen = group.classList.toggle('open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        // على الجوال: أغلق الدرج بعد اختيار رابط
        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                var link = e.target.closest('a');
                if (link && window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        }

        // البحث السريع: إظهار/إخفاء روابط القائمة بحسب النص.
        var search = document.getElementById('sidebarSearch');
        if (search && sidebar) {
            var allLinks = sidebar.querySelectorAll('.sidebar-link, .sidebar-sublink, .sidebar-group-toggle');
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                if (q === '') {
                    Array.prototype.forEach.call(allLinks, function (el) {
                        el.parentElement.style.display = '';
                    });
                    return;
                }
                Array.prototype.forEach.call(allLinks, function (el) {
                    var text = (el.textContent || '').toLowerCase();
                    var li = el.parentElement;
                    li.style.display = text.indexOf(q) !== -1 ? '' : 'none';
                });
                // افتح المجموعات التي ظهرت نتائجها.
                var groups = sidebar.querySelectorAll('.sidebar-group');
                Array.prototype.forEach.call(groups, function (g) {
                    var visible = g.querySelectorAll('.sidebar-subnav li:not([style*="display: none"])').length;
                    if (visible > 0) { g.classList.add('open'); }
                });
            });
        }
    });
})();
