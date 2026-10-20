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
    });
})();
