/* =====================================================================
   CD-012 — سلوك الشريط الجانبي (متعدد المستويات)
   - L1: الفئات (Categories) — طيّ/فتح.
   - L2: المجموعات (Groups) — طيّ/فتح داخل الفئة.
   - فتح فئة يغلق الفئات الأخرى (Smart accordion) للحفاظ على الترتيب.
   - البحث السريع يفلتر الروابط ويفتح المسارات المطابقة تلقائياً.
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

        // ======= L2: أكورديون المجموعات =======
        var groupToggles = document.querySelectorAll('.sidebar-group-toggle');
        Array.prototype.forEach.call(groupToggles, function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.sidebar-group');
                if (!group) { return; }
                var isOpen = group.classList.toggle('open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        // ======= L1: أكورديون الفئات (ذكي: يفتح فئة ويغلق البقية) =======
        var categoryToggles = document.querySelectorAll('.sidebar-category-toggle');
        Array.prototype.forEach.call(categoryToggles, function (btn) {
            btn.addEventListener('click', function () {
                var category = btn.closest('.sidebar-category');
                if (!category) { return; }
                var willOpen = !category.classList.contains('open');

                // أغلق باقي الفئات (طيّ ذكي على مستوى L1).
                Array.prototype.forEach.call(categoryToggles, function (other) {
                    var otherCat = other.closest('.sidebar-category');
                    if (otherCat && otherCat !== category) {
                        otherCat.classList.remove('open');
                        other.setAttribute('aria-expanded', 'false');
                    }
                });

                category.classList.toggle('open', willOpen);
                btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
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

        // ======= البحث السريع =======
        var search = document.getElementById('sidebarSearch');
        if (search && sidebar) {
            var allLinks = sidebar.querySelectorAll('.sidebar-link, .sidebar-sublink');

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

                // افتح الفئات والمجموعات التي ظهرت نتائج بداخلها.
                Array.prototype.forEach.call(sidebar.querySelectorAll('.sidebar-category'), function (cat) {
                    var anyVisible = Array.prototype.some.call(cat.querySelectorAll('.sidebar-sublink'), function (el) {
                        return el.parentElement.style.display !== 'none';
                    });
                    if (anyVisible) { cat.classList.add('open'); }
                });
                Array.prototype.forEach.call(sidebar.querySelectorAll('.sidebar-group'), function (g) {
                    var anyVisible = Array.prototype.some.call(g.querySelectorAll('.sidebar-sublink'), function (el) {
                        return el.parentElement.style.display !== 'none';
                    });
                    if (anyVisible) { g.classList.add('open'); }
                });
            });
        }
    });
})();
